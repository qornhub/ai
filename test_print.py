import sys
import json

print("Python works!")

if len(sys.argv) > 1:
    data = json.loads(sys.argv[1])
    print("Received:", data)
