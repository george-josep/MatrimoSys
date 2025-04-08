# Use an official Python runtime as a parent image
FROM python:3.9-slim

# Set the working directory in the container
WORKDIR /app

# Copy project files into the container
COPY . /app

# Upgrade pip (and optionally install any dependencies if you have them)
RUN pip install --upgrade pip
# RUN pip install --no-cache-dir -r requirements.txt

# Expose the port the application listens on (adjust if necessary)
EXPOSE 8000

# Run the application (update to the correct file)
CMD ["python", "MatrimoSys.py"]
