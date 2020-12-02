#include <stdio.h>
#include <string.h>
#include <sys/stat.h>

#include "zm.h"
#include "zm_font.h"
#include "zm_utils.h"

int ZmFont::ReadFontFile(const std::string &loc) {
  FILE *f = fopen(loc.c_str(), "rb");
  if ( !f ) return -1;  // FILE NOT FOUND

  struct stat st;
  stat(loc.c_str(), &st);

  font = new ZMFONT;

  // MAGIC + pad + BitmapHeaders
  size_t readsize = fread(&font[0], 1, 8 + (sizeof(ZMFONT_BH) * 4), f);
  if ( readsize < 8 + (sizeof(ZMFONT_BH) * 4) ) {
    delete font;
    font = nullptr;
    return -2; // EOF reached, invalid file
  }

  if ( memcmp(font->MAGIC, "ZMFNT", 5) != 0 )  // Check whether magic is correct
    return -3;

  for ( int i = 0; i < 4; i++ ) {
    /* Character Width cannot be greater than 64 as a row is represented as a uint64_t, 
       height cannot be greater than 200(arbitary number which i have chosen, shouldn't need more than this) and 
       idx should not be more than filesize
    */
    if ( (font->header[i].charWidth > 64 && font->header[i].charWidth == 0) || \
      (font->header[i].charHeight > 200 && font->header[i].charHeight == 0) || \
      (font->header[i].idx > st.st_size) ) {
        delete font;
        font = nullptr;
        return -4;
      }
  }

  datasize = st.st_size - (8 + sizeof(ZMFONT_BH) * 4);

  font->data = new uint64_t[datasize/sizeof(uint64_t)];
  readsize = fread(&font->data[0], 1, datasize, f);
  if( readsize < datasize) { // Shouldn't happen
    delete[] font->data;
    font->data = nullptr;
    delete font;
    font = nullptr;
    return -2;
  }
  fclose(f);
  return 0;
}

ZmFont::~ZmFont() {
  if ( font && font->data ) {
    delete[] font->data;
    font->data = nullptr;
  }

  if ( font ) {
    delete font;
    font = nullptr;
  }
}

uint64_t *ZmFont::GetBitmapData() {
  return &font->data[font->header[size].idx];
}
