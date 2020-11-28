#include <stdio.h>
#include "zm.h"
#include "zm_font.h"
#include "zm_utils.h"

int ZmFont::ReadFontFile( const std::string &loc ) {

  FILE *f = fopen(loc.c_str(), "rb");
  if( !f ) return -1; // FILE NOT FOUND
    
  font = (ZMFONT*)malloc(sizeof(ZMFONT));
  if(!font)
    return -1;
  fread(&font[0], 1, 8 + (sizeof(ZMFONT_BH) * 4), f);
  // Todo Check magic
  datasize = (font->header[3].idx * sizeof(uint64_t))+ (font->header[3].charHeight * font->header[3].numberofCodePoints * sizeof(uint64_t));
  font->data = (uint64_t*)malloc(datasize);
  fread(&font->data[0], 1, datasize, f);
  fclose(f);
  return 0;
}

uint64_t *ZmFont::GetBitmapDataForSize( const unsigned int size, uint16_t &charWidth, uint16_t &charHeight ) {
  charWidth = font->header[size - 1].charWidth;
  charHeight = font->header[size - 1].charHeight;
  return &font->data[font->header[size - 1].idx];
}