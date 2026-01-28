import os
import joblib
import pandas as pd
import requests

from typing import Optional, List, Dict, Any

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel


# ----------------------------
# Paths / Config
# ----------------------------
ARTIFACT_DIR = "artifacts"
PIPELINE_PATH = os.path.join(ARTIFACT_DIR, "pipeline.joblib")
LE_PATH = os.path.join(ARTIFACT_DIR, "label_encoder.joblib")

OLLAMA_URL = os.getenv("OLLAMA_URL", "http://127.0.0.1:11434/api/generate")
OLLAMA_MODEL = os.getenv("OLLAMA_MODEL", "phi3")  # e.g. phi3, llama3.2:1b, qwen2.5:1.5b

app = FastAPI(title="CropWise API")

# IMPORTANT:
# - allow_credentials must be False if you use "*" for origins
# - for local dev, explicitly allow the http.server origin(s)
app.add_middleware(
    CORSMiddleware,
    allow_origins=[
        "http://127.0.0.1:5500",
        "http://localhost:5500",
        "http://127.0.0.1:8000",
        "http://localhost:8000",
    ],
    allow_credentials=False,
    allow_methods=["*"],
    allow_headers=["*"],
)

pipeline = None
label_encoder = None


# ----------------------------
# Request Schemas
# ----------------------------
class PredictRequest(BaseModel):
    Temperature_C: float
    DayOfWeek: str
    SkyCondition: str
    Humidity_pct: float
    Wind_kmh: float
    Rain_mm: float
    top_k: int = 3


class ExplainRequest(BaseModel):
    question: str
    diseases: List[str] = []
    confidences: List[float] = []
    weather: Dict[str, Any] = {}

    # Python 3.9 compatible optional type:
    model: Optional[str] = None


# ----------------------------
# Helpers
# ----------------------------
def normalize_day(day: str) -> str:
    d = (day or "").strip().lower()
    m = {
        "mon": "Mon", "monday": "Mon",
        "tue": "Tue", "tuesday": "Tue",
        "wed": "Wed", "wednesday": "Wed",
        "thu": "Thu", "thursday": "Thu",
        "fri": "Fri", "friday": "Fri",
        "sat": "Sat", "saturday": "Sat",
        "sun": "Sun", "sunday": "Sun",
    }
    if d not in m:
        raise ValueError("DayOfWeek must be Mon/Tue/Wed/Thu/Fri/Sat/Sun (or full day name)")
    return m[d]


def normalize_sky(sky: str) -> str:
    s = (sky or "").strip().lower()
    if "thunder" in s:
        return "Thunderstorm"
    if "drizzle" in s:
        return "Drizzle"
    if "rain" in s or "shower" in s:
        return "Rain"
    if "fog" in s or "mist" in s:
        return "Fog"
    if "haze" in s or "smoke" in s:
        return "Haze"
    if "overcast" in s:
        return "Overcast"
    if "cloud" in s:
        return "Partly Cloudy" if "part" in s else "Cloudy"
    if "clear" in s or "sun" in s:
        return "Clear"
    return "Partly Cloudy"


def make_comment(feats: Dict[str, Any]) -> str:
    t = float(feats.get("Temperature_C", 0) or 0)
    h = float(feats.get("Humidity_pct", 0) or 0)
    r = float(feats.get("Rain_mm", 0) or 0)
    w = float(feats.get("Wind_kmh", 0) or 0)
    sky = str(feats.get("SkyCondition", "")).lower()

    wet = (r >= 1.0) or ("rain" in sky) or ("drizzle" in sky) or ("showers" in sky)
    humid = h >= 75
    cool = t <= 20
    warm = t >= 28
    windy = w >= 18

    if wet and cool:
        return "Wet conditions & cool temps."
    if wet and warm:
        return "Warm + wet conditions."
    if humid and not wet:
        return "Humid air; moisture risk."
    if not humid and not wet and warm:
        return "Hot & dry trend."
    if windy and humid:
        return "Windy + humid spread risk."
    return "Weather pattern suggests elevated risk."


def build_llm_prompt(question: str, diseases: List[str], confidences: List[float], weather: Dict[str, Any]) -> str:
    disease_lines = []
    for i, d in enumerate((diseases or [])[:3]):
        c = confidences[i] if i < len(confidences) else None
        if c is None:
            disease_lines.append(f"- {d}")
        else:
            disease_lines.append(f"- {d} ({c}%)")

    w = weather or {}
    weather_str = (
        f"Temp={w.get('Temperature_C')}°C, "
        f"Humidity={w.get('Humidity_pct')}%, "
        f"Wind={w.get('Wind_kmh')} km/h, "
        f"Rain={w.get('Rain_mm')} mm, "
        f"Sky={w.get('SkyCondition')}, "
        f"Day={w.get('DayOfWeek')}"
    )

    prompt = f"""
You are an agriculture assistant for CropWise.
User sees disease risk predictions from a model and wants actionable guidance.

Current weather: {weather_str}
Top predicted diseases:
{chr(10).join(disease_lines) if disease_lines else "- (none)"}

User question: {question}

Answer rules:
- Be concise, bullet points preferred.
- Explain why the weather may increase risk.
- Provide 3–6 practical actions (monitoring, prevention, safe steps).
- Add a short disclaimer: "This is guidance, not a definitive diagnosis."
""".strip()

    return prompt


# ----------------------------
# Startup: load artifacts
# ----------------------------
@app.on_event("startup")
def load_artifacts():
    global pipeline, label_encoder

    if not os.path.exists(PIPELINE_PATH):
        raise RuntimeError(f"Missing model file: {PIPELINE_PATH}")
    if not os.path.exists(LE_PATH):
        raise RuntimeError(f"Missing label encoder file: {LE_PATH}")

    pipeline = joblib.load(PIPELINE_PATH)
    label_encoder = joblib.load(LE_PATH)


# ----------------------------
# Routes
# ----------------------------
@app.get("/health")
def health():
    return {
        "ok": True,
        "pipeline_loaded": pipeline is not None,
        "label_encoder_loaded": label_encoder is not None,
        "ollama_model_default": OLLAMA_MODEL,
    }


@app.post("/predict")
def predict(req: PredictRequest):
    if pipeline is None or label_encoder is None:
        raise HTTPException(status_code=500, detail="Model not loaded")

    try:
        day = normalize_day(req.DayOfWeek)
        sky = normalize_sky(req.SkyCondition)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    X = pd.DataFrame([{
        "Temperature_C": float(req.Temperature_C),
        "DayOfWeek": day,
        "SkyCondition": sky,
        "Humidity_pct": float(req.Humidity_pct),
        "Wind_kmh": float(req.Wind_kmh),
        "Rain_mm": float(req.Rain_mm),
    }])

    feats = X.iloc[0].to_dict()
    comment = make_comment(feats)

    # If pipeline doesn't support proba, fallback to deterministic prediction
    if not hasattr(pipeline, "predict_proba"):
        pred_id = int(pipeline.predict(X)[0])
        combo = label_encoder.inverse_transform([pred_id])[0]  # "Crop||Disease"
        _crop, disease = combo.split("||", 1)
        return {
            "disease_cards": [{"disease": disease, "confidence_pct": 100.0, "comment": comment}],
            "used_features": feats
        }

    # Proba: classes are Crop||Disease, we aggregate into Disease-only
    proba = pipeline.predict_proba(X)[0]
    disease_to_prob: Dict[str, float] = {}

    for i, p in enumerate(proba):
        combo = label_encoder.classes_[i]  # e.g. "Rice||Blast"
        _crop, disease = combo.split("||", 1)
        disease_to_prob[disease] = disease_to_prob.get(disease, 0.0) + float(p)

    k = max(1, min(int(req.top_k), 3))
    top = sorted(disease_to_prob.items(), key=lambda kv: kv[1], reverse=True)[:k]

    cards = [{"disease": d, "confidence_pct": round(prob * 100.0, 1), "comment": comment} for d, prob in top]
    return {"disease_cards": cards, "used_features": feats}


@app.post("/explain")
def explain(req: ExplainRequest):
    q = (req.question or "").strip()
    if not q:
        raise HTTPException(status_code=400, detail="Question is required")

    model_name = (req.model or "").strip() or OLLAMA_MODEL
    prompt = build_llm_prompt(q, req.diseases or [], req.confidences or [], req.weather or {})

    try:
        r = requests.post(
            OLLAMA_URL,
            json={"model": model_name, "prompt": prompt, "stream": False},
            timeout=60,
        )

        if r.status_code != 200:
            raise HTTPException(status_code=500, detail=f"Ollama error: {r.text}")

        data = r.json()
        text = (data.get("response") or "").strip() or "No response from LLM."
        return {"answer": text, "model": model_name}

    except requests.exceptions.ConnectionError:
        raise HTTPException(
            status_code=500,
            detail="Cannot connect to Ollama. Start it with: `ollama serve` and pull model: `ollama pull phi3`.",
        )
    except requests.exceptions.Timeout:
        raise HTTPException(
            status_code=500,
            detail="Ollama timed out. Try a smaller model like phi3 or llama3.2:1b.",
        )
