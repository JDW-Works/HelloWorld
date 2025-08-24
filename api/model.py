from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import LSTM, Dense


def build_model():
    model = Sequential([
        LSTM(16, input_shape=(None, 1)),
        Dense(1)
    ])
    model.compile(optimizer='adam', loss='mse')
    return model


def load_sample_weights(model):
    # 此處為範例，實務上應載入預先訓練權重
    return model
