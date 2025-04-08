# Use an official Python runtime as a parent image
FROM python:3.9-slim

# Set the working directory in the container
WORKDIR /app

# Copy all project files into the container
COPY . /app

# Upgrade pip
RUN pip install --upgrade pip

# Install dependencies from requirements.txt if available
# Uncomment the next line if you have a requirements.txt file.
# RUN pip install --no-cache-dir -r requirements.txt

# Expose the port the application will run on
EXPOSE 8000

# Command to run the application
CMD ["python", "app.py"]
