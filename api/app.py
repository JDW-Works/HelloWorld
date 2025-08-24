from flask import Flask, request, jsonify
from model import build_model, load_sample_weights
import numpy as np

app = Flask(__name__)
model = load_sample_weights(build_model())


@app.route('/predict', methods=['POST'])
def predict():
    data = request.json.get('sequence', [])
    array = np.array(data).reshape((1, len(data), 1))
    pred = model.predict(array, verbose=0)
    return jsonify({'prediction': pred[0].tolist()})


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
