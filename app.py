import os
import joblib
import numpy as np
import pandas as pd

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel

ARTIFACT_DIR = "artifacts"
PIPELINE_PATH = os.path.join(ARTIFACT_DIR, "pipeline.joblib")
LE_PATH = os.path.join(ARTIFACT_DIR, "label_encoder.joblib")

app = FastAPI(title="CropWise API")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # dev only
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

pipeline = None
label_encoder = None


class PredictRequest(BaseModel):
    Temperature_C: float
    DayOfWeek: str
    SkyCondition: str
    Humidity_pct: float
    Wind_kmh: float
    Rain_mm: float
    top_k: int = 3


def normalize_day(day: str) -> str:
    d = day.strip().lower()
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
    s = sky.strip().lower()
    if "thunder" in s: return "Thunderstorm"
    if "drizzle" in s: return "Drizzle"
    if "rain" in s or "shower" in s: return "Rain"
    if "fog" in s or "mist" in s: return "Fog"
    if "haze" in s or "smoke" in s: return "Haze"
    if "overcast" in s: return "Overcast"
    if "cloud" in s:
        return "Partly Cloudy" if "part" in s else "Cloudy"
    if "clear" in s or "sun" in s: return "Clear"
    return "Partly Cloudy"


def make_comment(feats: dict) -> str:
    t = float(feats.get("Temperature_C", 0))
    h = float(feats.get("Humidity_pct", 0))
    r = float(feats.get("Rain_mm", 0))
    w = float(feats.get("Wind_kmh", 0))
    sky = str(feats.get("SkyCondition", "")).lower()

    wet = (r >= 1.0) or ("rain" in sky) or ("drizzle" in sky) or ("showers" in sky)
    humid = h >= 75
    cool = t <= 20
    warm = t >= 28
    windy = w >= 18

    if wet and cool: return "Wet conditions & cool temps."
    if wet and warm: return "Warm + wet conditions."
    if humid and not wet: return "Humid air; moisture risk."
    if not humid and not wet and warm: return "Hot & dry trend."
    if windy and humid: return "Windy + humid spread risk."
    return "Weather pattern suggests elevated risk."


@app.on_event("startup")
def load_artifacts():
    global pipeline, label_encoder
    if not os.path.exists(PIPELINE_PATH):
        raise RuntimeError(f"Missing: {PIPELINE_PATH}")
    if not os.path.exists(LE_PATH):
        raise RuntimeError(f"Missing: {LE_PATH}")
    pipeline = joblib.load(PIPELINE_PATH)
    label_encoder = joblib.load(LE_PATH)


@app.get("/health")
def health():
    return {"ok": True}


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

    # If no proba support
    if not hasattr(pipeline, "predict_proba"):
        pred_id = int(pipeline.predict(X)[0])
        combo = label_encoder.inverse_transform([pred_id])[0]  # Crop||Disease
        _crop, disease = combo.split("||", 1)
        return {
            "disease_cards": [{"disease": disease, "confidence_pct": 100.0, "comment": comment}],
            "used_features": feats
        }

    # Probabilities for Crop||Disease classes
    proba = pipeline.predict_proba(X)[0]

    # Aggregate to DISEASE-only by summing probs across crops
    disease_to_prob = {}
    for i, p in enumerate(proba):
        combo = label_encoder.classes_[i]  # Crop||Disease
        _crop, disease = combo.split("||", 1)
        disease_to_prob[disease] = disease_to_prob.get(disease, 0.0) + float(p)

    k = max(1, min(int(req.top_k), 3))
    top = sorted(disease_to_prob.items(), key=lambda kv: kv[1], reverse=True)[:k]

    cards = [{
        "disease": d,
        "confidence_pct": round(prob * 100.0, 1),
        "comment": comment
    } for d, prob in top]

    return {"disease_cards": cards, "used_features": feats}
