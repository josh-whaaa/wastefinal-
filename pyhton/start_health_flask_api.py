#!/usr/bin/env python3
"""
Flask API Startup Script for Health Risk Classification
This script provides an easy way to start the Flask API server
"""

import sys
import os
import argparse
from ml_health_risk_classifier import run_flask_server

def main():
    parser = argparse.ArgumentParser(description='Start Health Risk Classification Flask API')
    parser.add_argument('--host', default='0.0.0.0', help='Host to bind to (default: 0.0.0.0)')
    parser.add_argument('--port', type=int, default=5001, help='Port to bind to (default: 5001)')
    parser.add_argument('--debug', action='store_true', help='Enable debug mode')
    parser.add_argument('--production', action='store_true', help='Run in production mode with gunicorn')
    
    args = parser.parse_args()
    
    if args.production:
        print("Starting production server with gunicorn...")
        print(f"Command: gunicorn -w 4 -b {args.host}:{args.port} ml_health_risk_classifier:create_flask_app()")
        print("Make sure gunicorn is installed: pip install gunicorn")
        os.system(f"gunicorn -w 4 -b {args.host}:{args.port} 'ml_health_risk_classifier:create_flask_app()'")
    else:
        print("Starting development server...")
        run_flask_server(host=args.host, port=args.port, debug=args.debug)

if __name__ == "__main__":
    main()
