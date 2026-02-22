import base64
import json
import random
import time
import hashlib
import io
from flask import Flask, request, jsonify
from PIL import Image

app = Flask(__name__)

# Mock database of faces (in memory for this session)
# In a real app, you'd load known encodings from a DB or file
known_faces = []

def _bytes_from_image(image_data):
    if isinstance(image_data, str):
        s = image_data
        if s.startswith('data:'):
            try:
                base64_part = s.split(',', 1)[1]
                return base64.b64decode(base64_part)
            except Exception:
                return s.encode('utf-8')
        else:
            try:
                return base64.b64decode(s)
            except Exception:
                return s.encode('utf-8')
    return image_data

def generate_descriptor_from_image(image_data):
    b = _bytes_from_image(image_data)
    try:
        img = Image.open(io.BytesIO(b))
        img = img.convert('L')
        img = img.resize((16, 8))
        pixels = list(img.getdata())
        descriptor = [p / 255.0 for p in pixels]  # 128-length normalized vector
        return descriptor
    except Exception:
        image_hash = hashlib.sha256(b).digest()
        seed_int = int.from_bytes(image_hash, 'big')
        rnd = random.Random(seed_int)
        return [rnd.uniform(-1.0, 1.0) for _ in range(128)]

@app.route('/extract', methods=['POST'])
def extract():
    """
    Mock endpoint to extract face descriptor.
    Returns a deterministic 128-dimensional vector based on image hash.
    """
    data = request.get_json()
    if not data or 'image' not in data:
        return jsonify({'error': 'No image provided'}), 400

    # Simulate processing time
    time.sleep(0.1)

    image_data = data['image']
    
    # Generate deterministic descriptor
    mock_descriptor = generate_descriptor_from_image(image_data)

    return jsonify({'descriptor': mock_descriptor})

@app.route('/login', methods=['POST'])
def login():
    """
    DEPRECATED: Logic moved to PHP side for better control.
    Kept for backward compatibility but returns error to force update.
    """
    return jsonify({'error': 'Please use client-side comparison'}), 410

if __name__ == '__main__':
    print("Starting Mock Face Recognition API on port 5000...")
    app.run(host='0.0.0.0', port=5000, debug=True)
