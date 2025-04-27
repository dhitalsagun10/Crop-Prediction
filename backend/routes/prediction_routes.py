from flask import Blueprint, request, jsonify
import joblib
import pandas as pd
import os

prediction_bp = Blueprint('prediction', __name__)

# Load the trained model
model_path = os.path.join(os.path.dirname(__file__), '..', 'ml_model', 'nepal_crop_model.pkl')
model = joblib.load(model_path)

@prediction_bp.route('/predict', methods=['POST'])
def predict():
    data = request.json
    
    try:
        # Create input DataFrame matching training format
        input_data = pd.DataFrame([{
            'temperature': float(data.get('temperature')),
            'rainfall': float(data.get('rainfall')),
            'altitude': float(data.get('altitude')),
            'soil_ph': float(data.get('soil_ph'))
        }])
        
        # Get prediction (probability of success)
        probability = model.predict_proba(input_data)[0][1]
        