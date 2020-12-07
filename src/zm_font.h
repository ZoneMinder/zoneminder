#ifndef ZM_FONT_H
#define ZM_FONT_H

#include <inttypes.h>
#include <string>

#define NUM_FONT_SIZES 4

struct ZMFONT_BH{
    uint16_t charHeight;  // Height of every character
    uint16_t charWidth;  // Width of every character
    uint32_t numberofCodePoints;  // number of codepoints max 255 for now
    uint32_t idx;  // idx in data where data for the bitmap starts
    uint32_t pad;  // padding to round of the size
};

struct ZMFONT {
  char MAGIC[6];  // ZMFNT\0
  char pad[2];
  ZMFONT_BH header[NUM_FONT_SIZES];
  uint64_t *data;
};

class ZmFont {
 public:
    ~ZmFont();
    int ReadFontFile(const std::string &loc);
    ZMFONT *GetFont() { return font; }
    void SetFontSize(int _size) { size = _size; }
    uint64_t *GetBitmapData();
    uint16_t GetCharWidth() { return font->header[size].charWidth; }
    uint16_t GetCharHeight() { return font->header[size].charHeight; }

 private:
    int size = 0;
    size_t datasize = 0;
    ZMFONT *font = nullptr;
};

#endif
