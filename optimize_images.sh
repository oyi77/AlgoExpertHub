#!/bin/bash

# Find PNGs > 1MB
find main/public/asset/frontend -type f -name "*.png" -size +1M | while read -r img; do
    echo "Processing $img..."
    
    # 1. Optimize PNG in-place
    optipng -o2 "$img"
    
    # 2. Convert to WebP (lossless)
    webp_path="${img%.*}.webp"
    cwebp -q 80 "$img" -o "$webp_path"
    
    echo "  -> Optimized PNG and created $webp_path"
done

# Find JPEGs > 1MB
find main/public/asset/frontend -type f \( -name "*.jpg" -o -name "*.jpeg" \) -size +1M | while read -r img; do
    echo "Processing $img..."
    
    # 1. Optimize JPEG in-place
    jpegoptim -m85 --strip-all "$img"
    
    # 2. Convert to WebP
    webp_path="${img%.*}.webp"
    cwebp -q 80 "$img" -o "$webp_path"
    
    echo "  -> Optimized JPEG and created $webp_path"
done
