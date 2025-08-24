import pytest

try:
    from api.app import app
    tf_available = True
except Exception:
    tf_available = False


@pytest.mark.skipif(not tf_available, reason="TensorFlow 或 Flask 未安裝")
def test_predict():
    client = app.test_client()
    resp = client.post('/predict', json={'sequence': [0]*10})
    assert resp.status_code == 200
