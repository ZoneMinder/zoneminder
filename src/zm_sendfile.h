#ifndef ZM_SENDFILE_H
#define ZM_SENDFILE_H

#ifdef __cplusplus
extern "C" {
#endif

#if defined(HAVE_SENDFILE) && defined(HAVE_SENDFILE4_SUPPORT)
#include <sys/sendfile.h>
#elif defined(HAVE_SENDFILE) && defined(HAVE_SENDFILE7_SUPPORT)
#include <sys/types.h>
#include <sys/socket.h>
#include <sys/uio.h>
#else
#include <unistd.h>
#include <stdio.h>
#endif

/* Function to send the contents of a file. Will use sendfile or fall back to reading/writing */

ssize_t zm_sendfile(int out_fd, int in_fd, off_t *offset, size_t size) {
#if defined(HAVE_SENDFILE) && defined(HAVE_SENDFILE4_SUPPORT)
  ssize_t err = sendfile(out_fd, in_fd, offset, size);
  if (err < 0) {
    return -errno;
  }
  return err;

#elif defined(HAVE_SENDFILE) && defined(HAVE_SENDFILE7_SUPPORT)
  off_t sbytes;
  ssize_t err = sendfile(in_fd, out_fd, (offset ? *offset: 0), size, nullptr, &sbytes, 0);
  if (err && errno != EAGAIN)
    return -errno;
  return sbytes;
#else
  uint8_t buffer[4096];
  size_t chunk_size = (size > 4096 ? 4096 : size);

  ssize_t err = read(in_fd, buffer, chunk_size);
  if (err < 0) {
    Error("Unable to read %zu bytes of %zu: %s", chunk_size, size, strerror(errno));
    return -errno;
  }
  if (!err) {
    Error("Got EOF despite wanting to read %zu bytes", 4096);
    return err;
  }

  chunk_size = err;

  err = write(out_fd, buffer, chunk_size);
  if (err < 0) {
    Error("Unable to write %zu bytes: %s", chunk_size, strerror(errno));
    return -errno;
  } else if (err != chunk_size) {
    Debug(1, "Sent less than desired %zu < %zu", err, chunk_size);
  }

  return err;
#endif
}

#ifdef __cplusplus
}
#endif

#endif
