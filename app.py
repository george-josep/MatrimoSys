from flask import Flask

app = Flask(__name__)

@app.route("/")
def home():
    return "Welcome to MatrimoSys!"

if __name__ == "__main__":
    # The host "0.0.0.0" allows the app to be accessible from outside the container.
    app.run(host="0.0.0.0", port=8000, debug=True)
