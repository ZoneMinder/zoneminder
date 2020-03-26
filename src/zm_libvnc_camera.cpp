#include "zm.h"
#include "zm_signal.h"
#include "zm_libvnc_camera.h"

static int TAG_0;
static int TAG_1;
static int TAG_2;

static void GotFrameBufferUpdateCallback(rfbClient *rfb, int x, int y, int w, int h){
  VncPrivateData *data = (VncPrivateData *)rfbClientGetClientData(rfb, &TAG_0);
  data->buffer = rfb->frameBuffer;
}

static char* GetPasswordCallback(rfbClient* cl){
  return strdup((const char *)rfbClientGetClientData(cl, &TAG_1));
}

static rfbCredential* GetCredentialsCallback(rfbClient* cl, int credentialType){
  rfbCredential *c = (rfbCredential *)malloc(sizeof(rfbCredential));
  if(credentialType != rfbCredentialTypeUser) {
      return NULL;
  }

  c->userCredential.password = strdup((const char *)rfbClientGetClientData(cl, &TAG_1));
  c->userCredential.username = strdup((const char *)rfbClientGetClientData(cl, &TAG_2));
  return c;
}

VncCamera::VncCamera(
    unsigned int p_monitor_id,
    const std::string &host,
    const std::string &port,
    const std::string &user,
    const std::string &pass,
    int p_width,
    int p_height,
    int p_colours,
    int p_brightness,
    int p_contrast,
    int p_hue,
    int p_colour,
    bool p_capture,
    bool p_record_audio ) :
    Camera(
      p_monitor_id,
      VNC_SRC,
      p_width,
      p_height,
      p_colours,
      ZM_SUBPIX_ORDER_DEFAULT_FOR_COLOUR(p_colours),
      p_brightness,
      p_contrast,
      p_hue,
      p_colour,
      p_capture,
      p_record_audio
    ),
  mHost(host),
  mPort(port),
  mUser(user),
  mPass(pass)
{
  Debug(2, "Host:%s Port: %s User: %s Pass:%s", mHost.c_str(), mPort.c_str(), mUser.c_str(), mPass.c_str());
  if( capture )
    Initialise();
}


VncCamera::~VncCamera() {
  if( capture )
    Terminate();
}

void VncCamera::Initialise() {
  Debug(2, "Initializing Client");
  mRfb = rfbGetClient(8, 3, 4);
  rfbClientSetClientData(mRfb, &TAG_0, &mVncData);
  rfbClientSetClientData(mRfb, &TAG_1, (void *)mPass.c_str());
  rfbClientSetClientData(mRfb, &TAG_2, (void *)mUser.c_str());

  mVncData.bufferSize = width * height * 4;
  mVncData.buffer = (uint8_t *)malloc(mVncData.bufferSize * sizeof(uint8_t));
  
  mRfb->GotFrameBufferUpdate = GotFrameBufferUpdateCallback;
  mRfb->GetPassword = GetPasswordCallback;
  mRfb->GetCredential = GetCredentialsCallback;

  mRfb->programName = "Zoneminder VNC Monitor";
  mRfb->serverHost = strdup(mHost.c_str());
  mRfb->serverPort = atoi(mPort.c_str());
  rfbInitClient(mRfb, 0, nullptr);
}

void VncCamera::Terminate() {
  if(mVncData.buffer)
    free(mVncData.buffer);
  return;
}

int VncCamera::PrimeCapture() {
  Info("Priming capture from %s", mHost.c_str());
  return 0;
}

int VncCamera::PreCapture() {
  WaitForMessage(mRfb, 500);
  rfbBool res = HandleRFBServerMessage(mRfb);
  return res == TRUE ? 1 : -1 ;
}

int VncCamera::Capture(Image &image) {
  Debug(2, "Capturing");
  image.Assign(width, height, colours, subpixelorder, mVncData.buffer, width * height * 4);
  return 1;
}

int VncCamera::PostCapture() {
  return 0;
}

int VncCamera::CaptureAndRecord(Image &image, timeval recording, char* event_directory) {
  return 0;
}

int VncCamera::Close() {
  rfbClientCleanup(mRfb);
  return 0;
}