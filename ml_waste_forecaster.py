#!/usr/bin/env python3
"""
Waste Volume Forecasting Model
Predicts next week's waste volume for each barangay using time series analysis
"""

import sys
import json
import pandas as pd
import numpy as np
from sklearn.linear_model import LinearRegression
from sklearn.preprocessing import PolynomialFeatures
from sklearn.metrics import mean_absolute_error, mean_squared_error
import warnings
warnings.filterwarnings('ignore')

class WasteVolumeForecaster:
    def __init__(self):
        self.model = LinearRegression()
        self.poly_features = PolynomialFeatures(degree=2, include_bias=False)
        self.is_trained = False
        self.feature_names = [
            'avg_weekly_tons',
            'trend',
            'volatility',
            'recent_peak',
            'collection_consistency',
            'waste_growth_rate',
            'collection_frequency',
            'peak_to_avg_ratio',
            'day_of_week_pattern',
            'seasonal_factor'
        ]
        
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
        
        # Feature 9: Day of week pattern (weekend vs weekday)
        if len(dates) > 0:
            day_of_week = dates.dayofweek
            weekend_tons = np.mean(tons[day_of_week >= 5]) if np.any(day_of_week >= 5) else 0
            weekday_tons = np.mean(tons[day_of_week < 5]) if np.any(day_of_week < 5) else 0
            day_of_week_pattern = weekend_tons / max(weekday_tons, 0.001) if weekday_tons > 0 else 1
        else:
            day_of_week_pattern = 1
            
        # Feature 10: Seasonal factor (based on month)
        if len(dates) > 0:
            current_month = dates[-1].month
            # Simple seasonal adjustment (higher in summer months)
            seasonal_factor = 1.0 + 0.1 * np.sin(2 * np.pi * current_month / 12)
        else:
            seasonal_factor = 1.0
        
        features = np.array([
            avg_weekly_tons,
            trend,
            volatility,
            recent_peak,
            collection_consistency,
            waste_growth_rate,
            collection_frequency,
            peak_to_avg_ratio,
            day_of_week_pattern,
            seasonal_factor
        ])
        
        return features
    
    def create_training_data(self, barangay_data):
        """Create training data for forecasting model"""
        training_data = []
        training_targets = []
        
        for brgy_id, data in barangay_data.items():
            daily_counts = data['daily_counts']
            if len(daily_counts) < 14:  # Need at least 2 weeks of data
                continue
                
            # Create sliding windows for training with more samples
            dates = sorted(daily_counts.keys())
            # Create more training samples with different window sizes
            for window_size in [14, 21, 28]:  # 2, 3, 4 weeks
                for i in range(window_size, len(dates) - 7):  # Leave 7 days for prediction
                    # Use last window_size days as input
                    input_data = {date: daily_counts[date] for date in dates[i-window_size:i]}
                    # Use next 7 days as target
                    target_data = {date: daily_counts[date] for date in dates[i:i+7]}
                    
                    # Extract features from input data
                    features = self.extract_features(input_data)
                    
                    # Calculate target (next week's total)
                    next_week_tons = sum(target_data.values()) * 0.001
                    
                    training_data.append(features)
                    training_targets.append(next_week_tons)
        
        return np.array(training_data), np.array(training_targets)
    
    def train_model(self, barangay_data):
        """Train the forecasting model"""
        # Reduced debug output to prevent truncation
        X, y = self.create_training_data(barangay_data)
        
        if len(X) == 0:
            return False
            
        # Use polynomial features for better prediction
        X_poly = self.poly_features.fit_transform(X)
        
        # Train model (reduced debug output)
        self.model.fit(X_poly, y)
        
        self.is_trained = True
        return True
    
    def forecast_next_week(self, features):
        """Forecast next week's waste volume"""
        if not self.is_trained:
            # Fallback to simple prediction
            return self._simple_forecast(features)
            
        try:
            # Transform features
            features_poly = self.poly_features.transform([features])
            
            # Make prediction
            forecast = self.model.predict(features_poly)[0]
            
            # Ensure non-negative prediction
            forecast = max(0, forecast)
            
            # Calculate confidence based on feature quality
            confidence = self._calculate_confidence(features)
            
            return {
                'forecasted_tons': forecast,
                'confidence': confidence,
                'model_used': 'Polynomial Regression'
            }
        except Exception as e:
            print(f"Error in forecasting: {e}", file=sys.stderr)
            return self._simple_forecast(features)
    
    def _simple_forecast(self, features):
        """Simple fallback forecasting method"""
        avg_weekly_tons = features[0]
        trend = features[1]
        volatility = features[2]
        collection_consistency = features[4]
        
        # Simple linear projection
        forecast = avg_weekly_tons + (trend * 7)
        
        # Adjust for collection consistency
        forecast = forecast * (collection_consistency / 100)
        
        # Add some volatility
        forecast = forecast * (1 + volatility * 0.1)
        
        # Ensure non-negative
        forecast = max(0, forecast)
        
        # Calculate confidence
        confidence = min(95, max(30, 100 - volatility * 20))
        
        return {
            'forecasted_tons': forecast,
            'confidence': confidence,
            'model_used': 'Simple Linear Projection'
        }
    
    def _calculate_confidence(self, features):
        """Calculate confidence in the forecast"""
        volatility = features[2]
        collection_consistency = features[4]
        avg_weekly_tons = features[0]
        
        # Base confidence
        confidence = 70
        
        # Adjust based on data quality
        confidence += (collection_consistency / 100) * 20  # More data = higher confidence
        confidence -= volatility * 15  # More volatility = lower confidence
        
        # Adjust based on volume (higher volumes are more predictable)
        if avg_weekly_tons > 2:
            confidence += 10
        elif avg_weekly_tons > 1:
            confidence += 5
        
        return max(20, min(95, confidence))

def main():
    """Main function to handle command line input"""
    try:
        # Read input from stdin
        input_data = json.loads(sys.stdin.read())
        
        lookback_days = input_data.get('lookback_days', 14)
        barangay_data = input_data.get('barangay_data', {})
        
        # Initialize forecaster
        forecaster = WasteVolumeForecaster()
        
        # Train model
        forecaster.train_model(barangay_data)
        
        # Make forecasts
        forecasts = []
        for brgy_id, data in barangay_data.items():
            features = forecaster.extract_features(data['daily_counts'], lookback_days)
            forecast_result = forecaster.forecast_next_week(features)
            
            forecasts.append({
                'brgy_id': brgy_id,
                'barangay': data['barangay'],
                'latitude': data['latitude'],
                'longitude': data['longitude'],
                'forecasted_tons': round(forecast_result['forecasted_tons'], 3),
                'confidence': round(forecast_result['confidence'], 2),
                'model_used': forecast_result['model_used'],
                'features': {
                    'avg_weekly_tons': round(features[0], 2),
                    'trend': round(features[1], 2),
                    'volatility': round(features[2], 2),
                    'collection_consistency': round(features[4], 1)
                }
            })
        
        # Output results
        result = {
            'success': True,
            'lookback_days': lookback_days,
            'forecasts': forecasts,
            'model_info': {
                'type': 'Waste Volume Forecaster',
                'algorithm': 'Polynomial Regression with Time Series Features',
                'features': forecaster.feature_names,
                'trained': forecaster.is_trained
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
