import cv2
import sys
import numpy as np

def decode_qr(image):
    detector = cv2.QRCodeDetector()
    data, vertices_array, _ = detector.detectAndDecode(image)
    if vertices_array is not None and data:
        return data
    return None

def try_decoding_variants(gray):
    # 1. Otsu Inverse
    _, otsu_inv = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)
    data = decode_qr(otsu_inv)
    if data: return data

    # 2. Otsu Normal
    _, otsu = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    data = decode_qr(otsu)
    if data: return data

    # 3. CLAHE + Otsu
    clahe = cv2.createCLAHE(clipLimit=3.0, tileGridSize=(8,8))
    clahe_img = clahe.apply(gray)
    _, otsu_clahe = cv2.threshold(clahe_img, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    data = decode_qr(otsu_clahe)
    if data: return data

    # 4. Sharpen + Otsu
    kernel = np.array([[0, -1, 0],
                       [-1, 5, -1],
                       [0, -1, 0]])
    sharpened = cv2.filter2D(gray, -1, kernel)
    _, otsu_sharp = cv2.threshold(sharpened, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    data = decode_qr(otsu_sharp)
    if data: return data

    # 5. Upscale + Otsu
    upscaled = cv2.resize(gray, None, fx=2, fy=2, interpolation=cv2.INTER_CUBIC)
    _, otsu_up = cv2.threshold(upscaled, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    data = decode_qr(otsu_up)
    if data: return data

    # 6. Adaptive threshold
    adaptive = cv2.adaptiveThreshold(gray, 255,
                                     cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
                                     cv2.THRESH_BINARY, 11, 2)
    data = decode_qr(adaptive)
    if data: return data

    # 7. Morphological cleanup (on Otsu)
    kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (2,2))
    morph = cv2.morphologyEx(otsu, cv2.MORPH_CLOSE, kernel)
    data = decode_qr(morph)
    if data: return data

    return None

if __name__ == "__main__":
    image_path = sys.argv[1]
    original_image = cv2.imread(image_path)

    if original_image is None:
        print('')
        sys.exit()

    # Convert to grayscale
    gray = cv2.cvtColor(original_image, cv2.COLOR_BGR2GRAY)

    # Try multiple methods
    data = try_decoding_variants(gray)

    # Print result or empty string
    print(data if data else '')



# import cv2
# import sys

# def decode_qr(image):
#     detector = cv2.QRCodeDetector()
#     data, vertices_array, _ = detector.detectAndDecode(image)
#     if vertices_array is not None and data:
#         return data
#     return None

# image_path = sys.argv[1]
# original_image = cv2.imread(image_path)

# if original_image is None:
#     print('')
#     sys.exit()

# # Convert to grayscale
# gray = cv2.cvtColor(original_image, cv2.COLOR_BGR2GRAY)

# # First attempt: white QR on black background (binary inverse)
# _, otsu_image_inv = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)
# data = decode_qr(otsu_image_inv)

# # Second attempt: black QR on white background (normal binary)
# if not data:
#     _, otsu_image = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
#     data = decode_qr(otsu_image)

# # Print result or empty string if nothing detected
# print(data if data else '')
