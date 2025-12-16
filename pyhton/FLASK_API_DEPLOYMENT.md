# Flask API Deployment Guide

This guide explains how to deploy the Waste Volume Forecasting Flask API to an online server.

## Prerequisites

1. **Python 3.8+** installed on your server
2. **pip** package manager
3. **Virtual environment** (recommended)
4. **Domain name** and **SSL certificate** (for production)

## Installation Steps

### 1. Install Dependencies

```bash
# Install Python dependencies
pip install -r requirements.txt

# For production, also install gunicorn
pip install gunicorn
```

### 2. Test the API Locally

```bash
# Test the Flask API locally
python ml_waste_forecaster.py --server

# Or use the startup script
python start_flask_api.py --host 0.0.0.0 --port 5000 --debug
```

### 3. Configure PHP API

Update the Flask API URL in `api/get_waste_forecast.php`:

```php
$flaskApiUrl = 'http://your-domain.com:5000/forecast'; // Change to your server URL
$useFlaskApi = true; // Set to true to use Flask API
```

## Deployment Options

### Option 1: Development Server (Not Recommended for Production)

```bash
# Run on port 5000
python ml_waste_forecaster.py --server --host 0.0.0.0 --port 5000

# Or with custom settings
python start_flask_api.py --host 0.0.0.0 --port 5000 --debug
```

### Option 2: Production with Gunicorn (Recommended)

```bash
# Install gunicorn
pip install gunicorn

# Run with gunicorn
gunicorn -w 4 -b 0.0.0.0:5000 'ml_waste_forecaster:create_flask_app()'

# Or use the startup script
python start_flask_api.py --host 0.0.0.0 --port 5000 --production
```

### Option 3: Using systemd Service (Linux)

Create a systemd service file `/etc/systemd/system/waste-forecast-api.service`:

```ini
[Unit]
Description=Waste Volume Forecasting API
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/your/pyhton
Environment=PATH=/path/to/your/venv/bin
ExecStart=/path/to/your/venv/bin/gunicorn -w 4 -b 0.0.0.0:5000 'ml_waste_forecaster:create_flask_app()'
Restart=always

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl enable waste-forecast-api
sudo systemctl start waste-forecast-api
sudo systemctl status waste-forecast-api
```

### Option 4: Using Docker

Create a `Dockerfile`:

```dockerfile
FROM python:3.9-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install -r requirements.txt

COPY . .

EXPOSE 5000

CMD ["gunicorn", "-w", "4", "-b", "0.0.0.0:5000", "ml_waste_forecaster:create_flask_app()"]
```

Build and run:

```bash
docker build -t waste-forecast-api .
docker run -p 5000:5000 waste-forecast-api
```

## Nginx Configuration (Optional)

If you want to use Nginx as a reverse proxy:

```nginx
server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://127.0.0.1:5000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

## API Endpoints

- **GET** `/health` - Health check
- **POST** `/forecast` - Main forecasting endpoint
- **GET** `/forecast` - API documentation

## Testing the API

### Health Check
```bash
curl http://your-server:5000/health
```

### Forecast Request
```bash
curl -X POST http://your-server:5000/forecast \
  -H "Content-Type: application/json" \
  -d '{
    "lookback_days": 14,
    "barangay_data": {
      "brgy_1": {
        "barangay": "Test Barangay",
        "latitude": 10.0,
        "longitude": 122.0,
        "daily_counts": {
          "2024-01-01": 10,
          "2024-01-02": 15,
          "2024-01-03": 12
        }
      }
    }
  }'
```

## Security Considerations

1. **Firewall**: Only open necessary ports (5000 or 80/443)
2. **HTTPS**: Use SSL certificates for production
3. **Authentication**: Consider adding API keys for production use
4. **Rate Limiting**: Implement rate limiting for production
5. **CORS**: Configure CORS settings appropriately

## Monitoring

- Monitor server resources (CPU, memory)
- Set up log rotation
- Monitor API response times
- Set up health check monitoring

## Troubleshooting

### Common Issues

1. **Port already in use**: Change the port or kill the existing process
2. **Permission denied**: Check file permissions and user access
3. **Module not found**: Ensure all dependencies are installed
4. **Connection refused**: Check firewall and network settings

### Logs

Check logs for debugging:
```bash
# If using systemd
sudo journalctl -u waste-forecast-api -f

# If using gunicorn directly
# Logs will appear in the terminal
```

## Performance Optimization

1. **Worker Processes**: Adjust `-w` parameter in gunicorn based on CPU cores
2. **Memory**: Monitor memory usage and adjust worker count
3. **Caching**: Consider adding Redis for caching if needed
4. **Database**: Optimize database queries if using external data sources
