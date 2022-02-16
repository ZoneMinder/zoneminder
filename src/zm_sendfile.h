#ifndef ZM_SENDFILE_H
#define ZM_SENDFILE_H

#ifdef HAVE_SENDFILE4_SUPPORT
#include <sys/sendfile.h>
#elif HAVE_SENDFILE7_SUPPORT
#include <sys/types.h>
#include <sys/socket.h>
#include <sys/uio.h>
#else
#include <unistd.h>
#endif

/* Function to send the contents of a file. Will use sendfile or fall back to reading/writing */

ssize_t zm_sendfile(int out_fd, int in_fd, off_t *offset, size_t size) {
#ifdef HAVE_SENDFILE4_SUPPORT
  ssize_t err = sendfile(out_fd, in_fd, offset, size);
  if (err < 0) {
    return -errno;
  }
  return err;

#elif HAVE_SENDFILE7_SUPPORT
  ssize_t err = sendfile(in_fd, out_fd, *offset, size, nullptr, &size, 0);
  if (err && errno != EAGAIN)
    return -errno;
  return size;
#else
  uint8_t buffer[size];
  ssize_t err = read(in_fd, buffer, size);
  if (err < 0) {
    Error("Unable to read %zu bytes: %s", size, strerror(errno));
    return -errno;
  }

  err = fwrite(out_fd, buffer, size);
  if (err < 0) {
    Error("Unable to write %zu bytes: %s", size, strerror(errno));
    return -errno;
  }
  return err;
#endif
}

#endif // ZM_SENDFILE_H
