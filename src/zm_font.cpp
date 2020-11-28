#include <stdio.h>
#include <string.h>
#include <sys/stat.h>

#include "zm.h"
#include "zm_font.h"
#include "zm_utils.h"

int ZmFont::ReadFontFile( const std::string &loc ) {

  FILE *f = fopen(loc.c_str(), "rb");
  if( !f ) return -1; // FILE NOT FOUND

  struct stat st;
  stat(loc.c_str(), &st);
    
  font = (ZMFONT*)malloc(sizeof(ZMFONT));
  if( !font )
    return -2;
  fread(&font[0], 1, 8 + (sizeof(ZMFONT_BH) * 4), f); // MAGIC + pad + BitmapHeaders

  if(memcmp(font->MAGIC, "ZMFNT", 5) != 0) // Check whether magic is correct
    return -3;
  
  for(int i = 0; i < 4; i++)
  {
    /* Character Width cannot be greater than 64, 
       height cannot be greater than 200(arbitary number which i have chosen shouldn't need more than this) and 
       idx should not be more than filesize
    */
    if((font->header[i].charWidth > 64 && font->header[i].charWidth == 0) || (font->header[i].charHeight > 200 && font->header[i].charHeight == 0) || (font->header[i].idx > st.st_size))
      return -4;
  }

  datasize = (font->header[3].idx * sizeof(uint64_t))+ (font->header[3].charHeight * font->header[3].numberofCodePoints * sizeof(uint64_t));
  font->data = (uint64_t*)malloc(datasize);
  fread(&font->data[0], 1, datasize, f);
  fclose(f);
  return 0;
}

void ZmFont::FreeData()
{
  if(font->data) free(font->data);
  if(font) free(font);
}

uint64_t *ZmFont::GetBitmapDataForSize( const unsigned int size, uint16_t &charWidth, uint16_t &charHeight ) {
  charWidth = font->header[size - 1].charWidth;
  charHeight = font->header[size - 1].charHeight;
  return &font->data[font->header[size - 1].idx];
}