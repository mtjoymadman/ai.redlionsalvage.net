import pytesseract
from PIL import Image
import sys
import json
import re

image_path = sys.argv[1]
text = pytesseract.image_to_string(Image.open(image_path))
amount = re.search(r'\$?\d+(\.\d{2})?', text)
print(json.dumps({'amount': float(amount.group(0).replace('$', '')) if amount else None}))