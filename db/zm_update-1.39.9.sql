--
-- Add EncoderTemplates table for v2 of the encoder-templates feature.
--

CREATE TABLE IF NOT EXISTS `EncoderTemplates` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Encoder` varchar(32) NOT NULL,
  `Name` varchar(64) NOT NULL,
  `Description` text,
  `Params` text NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `Encoder` (`Encoder`),
  UNIQUE KEY `Encoder_Name` (`Encoder`, `Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `EncoderTemplates` (`Encoder`, `Name`, `Description`, `Params`) VALUES
('libx264', 'Balanced',
 '1080p recording with reasonable CPU cost. Good default for most cameras.',
 'preset=fast\ncrf=23\ng=30\nprofile=high\npix_fmt=yuv420p'),
('libx264', 'Archival (high quality)',
 'Slow encode for archival storage; substantially smaller files at higher CPU cost.',
 'preset=slow\ncrf=20\ng=30\nprofile=high\npix_fmt=yuv420p'),
('libx264', 'Low CPU',
 'Highest encoding speed for slow CPUs; quality and file size trade off.',
 'preset=ultrafast\ncrf=26\ng=30\nprofile=baseline\npix_fmt=yuv420p'),

('libx265', 'Balanced',
 '1080p HEVC recording with reasonable CPU cost. Significantly smaller files than x264 at similar quality.',
 'preset=fast\ncrf=25\ng=30\nprofile=main\npix_fmt=yuv420p'),
('libx265', 'Archival (high quality)',
 'Slow HEVC encode for archival storage.',
 'preset=slow\ncrf=22\ng=30\nprofile=main\npix_fmt=yuv420p'),
('libx265', 'Low CPU',
 'Highest HEVC encoding speed for slow CPUs.',
 'preset=ultrafast\ncrf=28\ng=30\nprofile=main\npix_fmt=yuv420p'),

('h264_nvenc', 'Balanced',
 '1080p H.264 on NVIDIA GPU; sane vbr+cq defaults, no B-frames for low latency.',
 'preset=p4\nrc=vbr\ncq=23\ng=30\nbf=0\nprofile=high\npix_fmt=nv12'),
('h264_nvenc', 'Low Power',
 'Faster preset for thermally-constrained NVIDIA hardware.',
 'preset=p1\nrc=vbr\ncq=26\ng=30\nbf=0\nprofile=high\npix_fmt=nv12'),

('hevc_nvenc', 'Balanced',
 '1080p HEVC on NVIDIA GPU; sane vbr+cq defaults, no B-frames.',
 'preset=p4\nrc=vbr\ncq=28\ng=30\nbf=0\nprofile=main\npix_fmt=nv12'),
('hevc_nvenc', 'Low Power',
 'Faster preset for thermally-constrained NVIDIA hardware.',
 'preset=p1\nrc=vbr\ncq=30\ng=30\nbf=0\nprofile=main\npix_fmt=nv12'),

('h264_vaapi', 'Balanced',
 '1080p H.264 via VA-API (Intel/AMD/Mesa); no B-frames.',
 'rc_mode=CQP\nqp=24\ng=30\nbf=0\nprofile=high\npix_fmt=nv12'),
('h264_vaapi', 'Low Power',
 'Lower-quality VA-API encode using the low_power codepath.',
 'rc_mode=CQP\nqp=27\ng=30\nbf=0\nprofile=high\npix_fmt=nv12\nlow_power=1'),

('hevc_vaapi', 'Balanced',
 '1080p HEVC via VA-API; no B-frames.',
 'rc_mode=CQP\nqp=27\ng=30\nbf=0\nprofile=main\npix_fmt=nv12'),
('hevc_vaapi', 'Low Power',
 'Lower-quality HEVC VA-API encode using the low_power codepath.',
 'rc_mode=CQP\nqp=30\ng=30\nbf=0\nprofile=main\npix_fmt=nv12\nlow_power=1');
