#!/bin/bash

CONTENT_DIR="nano_photos_content"
QUALITY=85
MAX_DIMENSION=1920

echo "Starting image compression..."
echo "Target: Images > 1MB"
echo "Quality: ${QUALITY}%, Max dimension: ${MAX_DIMENSION}px"
echo ""

count=0
total_saved=0

find "$CONTENT_DIR" -type f \( -iname "*.jpg" -o -iname "*.jpeg" -o -iname "*.png" \) -size +1M | while read -r file; do
    original_size=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null)
    original_size_mb=$(echo "scale=2; $original_size / 1048576" | bc)

    backup="${file}.backup"
    cp "$file" "$backup"

    convert "$file" -resize "${MAX_DIMENSION}x${MAX_DIMENSION}>" -quality ${QUALITY} "$file"

    new_size=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null)
    new_size_mb=$(echo "scale=2; $new_size / 1048576" | bc)
    saved=$(echo "scale=2; $original_size - $new_size" | bc)
    saved_mb=$(echo "scale=2; $saved / 1048576" | bc)

    if [ "$new_size" -lt "$original_size" ]; then
        rm "$backup"
        echo "✓ $(basename "$file"): ${original_size_mb}MB -> ${new_size_mb}MB (saved ${saved_mb}MB)"
        count=$((count + 1))
        total_saved=$(echo "$total_saved + $saved" | bc)
    else
        mv "$backup" "$file"
        echo "✗ $(basename "$file"): No improvement, keeping original"
    fi
done

echo ""
echo "Compression complete!"
echo "Files compressed: $count"
total_saved_mb=$(echo "scale=2; $total_saved / 1048576" | bc)
echo "Total space saved: ${total_saved_mb}MB"
