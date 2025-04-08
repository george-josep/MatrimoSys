# Use an official Python runtime as a parent image
FROM python:3.9-slim

# Set the working directory in the container
WORKDIR /app

# Copy the project files into the container
COPY . /app

# Upgrade pip (optional) and install any needed packages manually
RUN pip install --upgrade pip
# For example, if you need to install Flask manually, uncomment the following line:
# RUN pip install --no-cache-dir Flask==2.2.2

# Expose the port your application will run on
EXPOSE 8000

# Define the command to run your application
CMD ["python", "app.py"]
