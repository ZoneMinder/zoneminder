#!/bin/python

import struct

GOOD_MAGIC = b"ZMFNT\0"
BAD_MAGIC = b"ABCDE\0"
NUM_FONT_SIZES = 4

ZMFNT_VERSION = 1


class FontFile:
    def __init__(self, path):
        self.path = path

    def write_file_header(self, magic):
        with open(self.path, "wb") as f:
            f.write(magic)
            f.write(struct.pack("B", ZMFNT_VERSION))
            f.write(struct.pack("B", 0))  # pad

    def write_bm_header(self, height, width, cp_count, idx, padding):
        with open(self.path, "ab") as f:
            f.write(struct.pack("HHIIBBBB", height, width, cp_count, idx, padding, 0, 0, 0))

    def write_codepoints(self, value, height, count):
        with open(self.path, "ab") as f:
            for _ in range(height * count):
                f.write(struct.pack("Q", value))


font = FontFile("01_bad_magic.zmfnt")
font.write_file_header(BAD_MAGIC)

# height, width and number of codepoints out of bounds
font = FontFile("02_variant_invalid.zmfnt")
font.write_file_header(GOOD_MAGIC)
font.write_bm_header(201, 65, 256, 0, 2)

# mismatch between number of codepoints specified in header and actually stored ones
font = FontFile("03_missing_cps.zmfnt")
font.write_file_header(GOOD_MAGIC)
offs = 0
for _ in range(NUM_FONT_SIZES):
    font.write_bm_header(10, 10, 10, offs, 2)
    offs += 10 * 10
for _ in range(NUM_FONT_SIZES):
    font.write_codepoints(1, 10, 9)

font = FontFile("04_valid.zmfnt")
font.write_file_header(GOOD_MAGIC)
offs = 0
for i in range(NUM_FONT_SIZES):
    font.write_bm_header(10 + i, 10 + i, 10, offs, 2)
    offs += 10 * (10 + i)
for i in range(NUM_FONT_SIZES):
    font.write_codepoints(i, 10 + i, 10)
