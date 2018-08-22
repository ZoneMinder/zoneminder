#ifndef ZM_SENDFILE_H
#define ZM_SENDFILE_H

#ifdef __cplusplus
extern "C" {
#endif

#ifdef HAVE_SENDFILE4_SUPPORT
#include <sys/sendfile.h>
int zm_sendfile(int out_fd, int in_fd, off_t *offset, size_t size) {
  int err;

  err = sendfile(out_fd, in_fd, offset, size);
  if (err < 0)
    return -errno;

  return err;
}
#elif HAVE_SENDFILE7_SUPPORT
#include <sys/types.h>
#include <sys/socket.h>
#include <sys/uio.h>
int zm_sendfile(int out_fd, int in_fd, off_t *offset, off_t size) {
  int err;
  err = sendfile(in_fd, out_fd, *offset, size, NULL, &size, 0);
  if (err && errno != EAGAIN)
    return -errno;

  if (size) {
    *offset += size;
    return size;
  }

  return -EAGAIN;
}
#else
#error "Your platform does not support sendfile. Sorry."
#endif

#ifdef __cplusplus
}
#endif

#endif
