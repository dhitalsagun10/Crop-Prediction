import pandas as pd
from sklearn.linear_model import LogisticRegression
import joblib

# Nepal-specific crop data template
data = {
    'temperature': [28, 15, 22, 18, 25],  # Nepal temp range
    'rainfall': [2500, 600, 1800, 900, 2000],  # Monsoon patterns
    'altitude': [300, 1500, 1200, 800, 2000],  # Terai/Hill/Mountain
    'soil_ph': [5.8, 6.2, 6.5, 5.9, 6.1],      # Common Nepal soil pH
    'crop_success': [1, 0, 1, 0, 1]            # Binomial target
}

df = pd.DataFrame(data)
model = LogisticRegression(random_state=42)
model.fit(df[['temperature', 'rainfall', 'altitude', 'soil_ph']], df['crop_success'])
joblib.dump(model, 'nepal_crop_model.pkl')