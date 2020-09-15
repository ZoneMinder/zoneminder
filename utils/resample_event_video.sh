#!/bin/sh

FFMPEG=`which ffmpeg`;

if [ -z "$FFMPEG" ]; then
  echo "You must install the ffmpeg package.  Try sudo apt-get install ffmpeg";
  exit;
fi

for i in "$@"
do
case $i in
 -r=*|--rate=*)
   FPS="${i#*=}"
   shift
   ;;
 -f=*|--file=*)
   VIDEO_FILE="${i#*=}"
   shift
   ;;

 *)
   EVENT_PATH="${i#*=}"
   shift
   ;;
esac
done

if [ -z "$EVENT_PATH" ]; then
  echo "You must specify the path to the event.";
  exit 255
fi;

if [ -z "$FPS" ]; then
  echo "You must specify the new fps.";
  exit 255
fi;

$FFMPEG -i "$EVENT_PATH/$VIDEO_FILE" -filter:v fps=fps=$FPS "$EVENT_PATH/output.mp4"
echo $?
if [ $? -eq 0 ]; then
  mv "$EVENT_PATH/output.mp4" "$EVENT_PATH/$VIDEO_FILE"
fi;

exit 0;
