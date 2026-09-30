/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for contributors.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_event.h"

#include <cerrno>
#include <cstdio>
#include <string>
#include <sys/stat.h>
#include <unistd.h>

TEST_CASE("ErrnoMeansNoHardLinkSupport") {
  SECTION("errnos that mean the filesystem has no hard links") {
    REQUIRE(ErrnoMeansNoHardLinkSupport(ENOSYS));
    REQUIRE(ErrnoMeansNoHardLinkSupport(EOPNOTSUPP));
    REQUIRE(ErrnoMeansNoHardLinkSupport(ENOTSUP));
  }

  SECTION("EPERM is Linux only") {
#ifndef __FreeBSD__
    // What exFAT and FAT report; see zoneminder/zoneminder#5048.
    REQUIRE(ErrnoMeansNoHardLinkSupport(EPERM));
#else
    // FreeBSD reports absent link support as EOPNOTSUPP and uses EPERM only for
    // a directory source, an immutable or append-only source, and an immutable
    // target directory. Renaming on those would paper over a real error.
    REQUIRE_FALSE(ErrnoMeansNoHardLinkSupport(EPERM));
#endif
  }

  SECTION("errnos that mean something else went wrong") {
    // A cross-device link must stay an error: rename() cannot do it either,
    // so falling back would just fail a second time.
    REQUIRE_FALSE(ErrnoMeansNoHardLinkSupport(EXDEV));
    REQUIRE_FALSE(ErrnoMeansNoHardLinkSupport(ENOENT));
    REQUIRE_FALSE(ErrnoMeansNoHardLinkSupport(EACCES));
    REQUIRE_FALSE(ErrnoMeansNoHardLinkSupport(EEXIST));
    REQUIRE_FALSE(ErrnoMeansNoHardLinkSupport(ENOSPC));
    REQUIRE_FALSE(ErrnoMeansNoHardLinkSupport(EMLINK));
    REQUIRE_FALSE(ErrnoMeansNoHardLinkSupport(EROFS));
    REQUIRE_FALSE(ErrnoMeansNoHardLinkSupport(0));
  }
}

TEST_CASE("Event video finalization fallback") {
  // Mirrors what ~Event does with the incomplete.* file, so the rename path
  // that filesystems without hard links take is exercised end to end.
  char dir_template[] = "/tmp/zm.event.XXXXXX";
  const char *dir = mkdtemp(dir_template);
  REQUIRE(dir != nullptr);

  const std::string incomplete = std::string(dir) + "/incomplete.h264.mp4";
  const std::string final_name = std::string(dir) + "/1-video.h264.mp4";

  FILE *fp = fopen(incomplete.c_str(), "w");
  REQUIRE(fp != nullptr);
  fputs("video", fp);
  fclose(fp);

  SECTION("rename leaves only the final name in place") {
    REQUIRE(rename(incomplete.c_str(), final_name.c_str()) == 0);

    struct stat st;
    REQUIRE(stat(final_name.c_str(), &st) == 0);
    // Nothing left to unlink afterwards, which is why video_file_linked stays
    // false on this path.
    REQUIRE(stat(incomplete.c_str(), &st) != 0);

    REQUIRE(unlink(final_name.c_str()) == 0);
  }

  SECTION("link leaves both names pointing at one inode") {
    // The normal path on a filesystem that does support hard links.
    REQUIRE(link(incomplete.c_str(), final_name.c_str()) == 0);

    struct stat incomplete_stat, final_stat;
    REQUIRE(stat(incomplete.c_str(), &incomplete_stat) == 0);
    REQUIRE(stat(final_name.c_str(), &final_stat) == 0);
    REQUIRE(incomplete_stat.st_ino == final_stat.st_ino);
    // ~Event counts distinct inodes so the video is not double counted
    // toward DiskSpace while both names exist.
    REQUIRE(final_stat.st_nlink == 2);

    REQUIRE(unlink(incomplete.c_str()) == 0);
    REQUIRE(stat(final_name.c_str(), &final_stat) == 0);
    REQUIRE(final_stat.st_nlink == 1);

    REQUIRE(unlink(final_name.c_str()) == 0);
  }

  rmdir(dir);
}
