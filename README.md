# CropWise (local demo)

This bundle wires your live-weather dashboard (`index.html`) to a local FastAPI model API (`app.py`).

## Folder layout

```
cropwise/
  app.py
  index.html
  requirements.txt
  artifacts/
    pipeline.joblib
    label_encoder.joblib
```

## 1) Put your artifacts in place

Copy these files into `artifacts/`:
- `pipeline.joblib`
- `label_encoder.joblib`

(You generated them in Kaggle and downloaded the zip.)

## 2) Install dependencies

From inside the `cropwise/` folder:

```bash
pip install -r requirements.txt
```

## 3) Run the API

```bash
uvicorn app:app --reload --port 8000
```

Check health:
- http://localhost:8000/health

## 4) Serve the webpage

In a second terminal (still inside `cropwise/`):

```bash
python -m http.server 5500
```

Open:
- http://localhost:5500/index.html

## What happens

- `index.html` fetches live weather (Open-Meteo + browser GPS)
- then calls `POST http://localhost:8000/predict`
- the API returns Top-K `crop + disease` pairs
- the page renders the list under "Probable Disease Risk"

