#ifndef ZM_FONT_H
#define ZM_FONT_H

#include <inttypes.h>
#include <string>

struct ZMFONT_BH{
    uint16_t charHeight; // Height of every character
    uint16_t charWidth; // Width of every character
    uint32_t numberofCodePoints; // number of codepoints max 255 for now
    uint32_t idx; // idx in data where data for the bitmap starts
    uint32_t pad; // padding to round of the size
};

struct ZMFONT {
  char MAGIC[6]; //ZMFNT\0
  char pad[2];
  ZMFONT_BH header[4];
  uint64_t *data;
};

class ZmFont {
    public:
        int ReadFontFile( const std::string &loc );
        ZMFONT *GetFont(){ return font; }
        uint64_t *GetBitmapDataForSize( const unsigned int size, uint16_t &charWidth, uint16_t &charHeight );
        void FreeData();

    private:
      size_t datasize = 0;
      ZMFONT *font = nullptr;
};

#endif