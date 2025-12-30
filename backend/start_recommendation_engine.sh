#!/bin/bash

# Architect Recommendation Engine Startup Script

echo "🏗️ Starting Architect Recommendation Engine..."

# Check if Python is installed
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 is not installed. Please install Python3 first."
    exit 1
fi

# Check if pip is installed
if ! command -v pip3 &> /dev/null; then
    echo "❌ pip3 is not installed. Please install pip3 first."
    exit 1
fi

# Install required packages
echo "📦 Installing required packages..."
pip3 install -r requirements.txt

# Create models directory
mkdir -p models

# Test the API before starting
echo "🧪 Testing API setup..."
python3 test_api.py

# Start the recommendation API
echo "🚀 Starting Architect Recommendation API on port 5001..."
echo "📊 Using existing architect data from your database..."
echo "🌐 CORS enabled for http://localhost:3000"
python3 architect_recommendation_api.py
