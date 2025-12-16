#!/usr/bin/env python3
"""
Health Risk Classification Model
Predicts health risk levels for barangays based on waste collection patterns
"""

import sys
import json
import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import classification_report, accuracy_score
import warnings
warnings.filterwarnings('ignore')

class HealthRiskClassifier:
    def __init__(self):
        self.model = RandomForestClassifier(
            n_estimators=100,
            max_depth=10,
            random_state=42,
            class_weight='balanced'
        )
        self.scaler = StandardScaler()
        self.feature_names = [
            'avg_weekly_tons',
            'trend',
            'volatility', 
            'recent_peak',
            'collection_consistency',
            'waste_growth_rate',
            'collection_frequency',
            'peak_to_avg_ratio'
        ]
        self.is_trained = False
        
    def extract_features(self, daily_counts, lookback_days=14):
        """Extract features from daily waste collection data"""
        if not daily_counts or len(daily_counts) == 0:
            return np.zeros(len(self.feature_names))
            
        # Convert to numpy array and sort by date
        counts = np.array(list(daily_counts.values()))
        dates = pd.to_datetime(list(daily_counts.keys()))
        
        # Sort by date
        sorted_indices = np.argsort(dates)
        counts = counts[sorted_indices]
        dates = dates[sorted_indices]
        
        # Convert counts to tons (assuming 1 count = 0.001 tons)
        tons = counts * 0.001
        
        # Feature 1: Average weekly tons
        avg_weekly_tons = np.mean(tons) * 7 if len(tons) > 0 else 0
        
        # Feature 2: Trend (linear regression slope)
        if len(tons) > 1:
            x = np.arange(len(tons))
            trend = np.polyfit(x, tons, 1)[0] if len(tons) > 1 else 0
        else:
            trend = 0
            
        # Feature 3: Volatility (standard deviation)
        volatility = np.std(tons) if len(tons) > 1 else 0
        
        # Feature 4: Recent peak (max in last 7 days)
        recent_peak = np.max(tons[-7:]) if len(tons) > 0 else 0
        
        # Feature 5: Collection consistency (% of days with collection)
        non_zero_days = np.sum(counts > 0)
        collection_consistency = (non_zero_days / len(counts)) * 100 if len(counts) > 0 else 0
        
        # Feature 6: Waste growth rate
        if len(tons) >= 7:
            first_week = np.mean(tons[:7]) if len(tons) >= 7 else 0
            last_week = np.mean(tons[-7:]) if len(tons) >= 7 else 0
            waste_growth_rate = ((last_week - first_week) / max(first_week, 0.001)) * 100
        else:
            waste_growth_rate = 0
            
        # Feature 7: Collection frequency (collections per week)
        collection_frequency = (non_zero_days / max(len(counts), 1)) * 7
        
        # Feature 8: Peak to average ratio
        peak_to_avg_ratio = np.max(tons) / max(np.mean(tons), 0.001) if len(tons) > 0 else 1
        
        features = np.array([
            avg_weekly_tons,
            trend,
            volatility,
            recent_peak,
            collection_consistency,
            waste_growth_rate,
            collection_frequency,
            peak_to_avg_ratio
        ])
        
        return features
    
    def create_synthetic_training_data(self, barangay_data):
        """Create synthetic training data based on historical patterns"""
        training_data = []
        training_labels = []
        
        for brgy_id, data in barangay_data.items():
            daily_counts = data['daily_counts']
            if len(daily_counts) < 7:  # Need at least a week of data
                continue
                
            # Extract features
            features = self.extract_features(daily_counts)
            
            # Create synthetic labels based on rules
            avg_weekly_tons = features[0]
            trend = features[1]
            volatility = features[2]
            recent_peak = features[3]
            collection_consistency = features[4]
            
            # Rule-based labeling for training
            if avg_weekly_tons >= 3.0 or recent_peak >= 3.0:
                label = 2  # High risk
            elif avg_weekly_tons >= 1.0 or (trend > 0.1 and volatility > 0.5):
                label = 1  # Medium risk
            else:
                label = 0  # Low risk
                
            # Adjust based on collection consistency
            if collection_consistency < 50:
                label = min(2, label + 1)  # Increase risk for poor collection
                
            training_data.append(features)
            training_labels.append(label)
            
            # Generate synthetic variations for better training
            for _ in range(3):
                noise = np.random.normal(0, 0.1, len(features))
                synthetic_features = features + noise
                synthetic_features = np.maximum(synthetic_features, 0)  # Ensure non-negative
                training_data.append(synthetic_features)
                training_labels.append(label)
        
        return np.array(training_data), np.array(training_labels)
    
    def train_model(self, barangay_data):
        """Train the classification model"""
        print("Creating training data...", file=sys.stderr)
        X, y = self.create_synthetic_training_data(barangay_data)
        
        if len(X) == 0:
            print("No training data available", file=sys.stderr)
            return False
            
        # Scale features
        X_scaled = self.scaler.fit_transform(X)
        
        # Split data
        X_train, X_test, y_train, y_test = train_test_split(
            X_scaled, y, test_size=0.2, random_state=42, stratify=y
        )
        
        # Train model
        print("Training Random Forest model...", file=sys.stderr)
        self.model.fit(X_train, y_train)
        
        # Evaluate
        y_pred = self.model.predict(X_test)
        accuracy = accuracy_score(y_test, y_pred)
        print(f"Model accuracy: {accuracy:.3f}", file=sys.stderr)
        
        self.is_trained = True
        return True
    
    def predict_risk(self, features):
        """Predict risk level for given features"""
        if not self.is_trained:
            # Fallback to rule-based prediction
            return self._rule_based_prediction(features)
            
        try:
            features_scaled = self.scaler.transform([features])
            prediction = self.model.predict(features_scaled)[0]
            probabilities = self.model.predict_proba(features_scaled)[0]
            
            # Add realistic uncertainty to confidence
            max_prob = np.max(probabilities)
            # Reduce confidence based on data quality and uncertainty
            uncertainty_factor = 0.1  # 10% uncertainty
            confidence = (max_prob * (1 - uncertainty_factor) + uncertainty_factor * 0.5) * 100
            confidence = min(95, max(60, confidence))  # Cap between 60-95%
            
            # Ensure we have 3 classes (low, medium, high)
            prob_dict = {}
            if len(probabilities) >= 3:
                prob_dict = {
                    'low': probabilities[0] * 100,
                    'medium': probabilities[1] * 100,
                    'high': probabilities[2] * 100
                }
            elif len(probabilities) == 2:
                prob_dict = {
                    'low': probabilities[0] * 100,
                    'medium': probabilities[1] * 100,
                    'high': 0
                }
            else:
                prob_dict = {
                    'low': 100 if prediction == 0 else 0,
                    'medium': 100 if prediction == 1 else 0,
                    'high': 100 if prediction == 2 else 0
                }
            
            return {
                'predicted_risk': prediction,
                'confidence': confidence,
                'probabilities': prob_dict
            }
        except Exception as e:
            print(f"Error in prediction: {e}", file=sys.stderr)
            return self._rule_based_prediction(features)
    
    def _rule_based_prediction(self, features):
        """Fallback rule-based prediction"""
        avg_weekly_tons, trend, volatility, recent_peak, collection_consistency = features[:5]
        
        risk_score = 0
        if avg_weekly_tons >= 3.0:
            risk_score += 3
        elif avg_weekly_tons >= 1.0:
            risk_score += 2
        else:
            risk_score += 1
            
        if trend > 0.1:
            risk_score += 1
        if volatility > 1.0:
            risk_score += 1
        if recent_peak >= 3.0:
            risk_score += 1
        if collection_consistency < 50:
            risk_score += 1
            
        if risk_score >= 5:
            predicted_risk = 2
            confidence = 85
        elif risk_score >= 3:
            predicted_risk = 1
            confidence = 75
        else:
            predicted_risk = 0
            confidence = 70
            
        return {
            'predicted_risk': predicted_risk,
            'confidence': confidence,
            'probabilities': {
                'low': 100 if predicted_risk == 0 else 0,
                'medium': 100 if predicted_risk == 1 else 0,
                'high': 100 if predicted_risk == 2 else 0
            }
        }
    
    def predict_next_week_tons(self, features):
        """Predict next week's waste volume"""
        avg_weekly_tons = features[0]
        trend = features[1]
        
        # Simple linear prediction
        predicted_tons = avg_weekly_tons + (trend * 7)
        return max(0, predicted_tons)

def main():
    """Main function to handle command line input"""
    try:
        # Read input from stdin
        input_data = json.loads(sys.stdin.read())
        
        lookback_days = input_data.get('lookback_days', 14)
        barangay_data = input_data.get('barangay_data', {})
        
        # Initialize classifier
        classifier = HealthRiskClassifier()
        
        # Train model
        if not classifier.train_model(barangay_data):
            print("Training failed, using rule-based predictions", file=sys.stderr)
        
        # Make predictions
        predictions = []
        for brgy_id, data in barangay_data.items():
            features = classifier.extract_features(data['daily_counts'], lookback_days)
            prediction_result = classifier.predict_risk(features)
            predicted_tons = classifier.predict_next_week_tons(features)
            
            # Convert numeric risk to string
            risk_levels = ['low', 'medium', 'high']
            predicted_risk_str = risk_levels[prediction_result['predicted_risk']]
            
            predictions.append({
                'brgy_id': brgy_id,
                'barangay': data['barangay'],
                'latitude': data['latitude'],
                'longitude': data['longitude'],
                'predicted_risk': predicted_risk_str,
                'predicted_tons': round(predicted_tons, 3),
                'confidence': round(prediction_result['confidence'], 2),
                'probabilities': prediction_result['probabilities'],
                'features': {
                    'avg_weekly_tons': round(features[0], 3),
                    'trend': round(features[1], 3),
                    'volatility': round(features[2], 3),
                    'recent_peak': round(features[3], 3),
                    'collection_consistency': round(features[4], 2),
                    'waste_growth_rate': round(features[5], 2),
                    'collection_frequency': round(features[6], 2),
                    'peak_to_avg_ratio': round(features[7], 2)
                }
            })
        
        # Output results
        result = {
            'success': True,
            'lookback_days': lookback_days,
            'predictions': predictions,
            'model_info': {
                'type': 'Random Forest Classifier',
                'features': classifier.feature_names,
                'algorithm': 'sklearn.ensemble.RandomForestClassifier',
                'trained': classifier.is_trained
            }
        }
        
        print(json.dumps(result, indent=2))
        
    except Exception as e:
        error_result = {
            'success': False,
            'error': str(e)
        }
        print(json.dumps(error_result, indent=2))
        sys.exit(1)

if __name__ == "__main__":
    main()
