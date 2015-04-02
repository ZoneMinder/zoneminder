package ONVIF::Device::Types::CodingCapabilities;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/schema' };

our $XML_ATTRIBUTE_CLASS;
undef $XML_ATTRIBUTE_CLASS;

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}

use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

Class::Std::initialize();

{ # BLOCK to scope variables

my %AudioEncodingCapabilities_of :ATTR(:get<AudioEncodingCapabilities>);
my %AudioDecodingCapabilities_of :ATTR(:get<AudioDecodingCapabilities>);
my %VideoDecodingCapabilities_of :ATTR(:get<VideoDecodingCapabilities>);

__PACKAGE__->_factory(
    [ qw(        AudioEncodingCapabilities
        AudioDecodingCapabilities
        VideoDecodingCapabilities

    ) ],
    {
        'AudioEncodingCapabilities' => \%AudioEncodingCapabilities_of,
        'AudioDecodingCapabilities' => \%AudioDecodingCapabilities_of,
        'VideoDecodingCapabilities' => \%VideoDecodingCapabilities_of,
    },
    {
        'AudioEncodingCapabilities' => 'ONVIF::Device::Types::AudioEncoderConfigurationOptions',
        'AudioDecodingCapabilities' => 'ONVIF::Device::Types::AudioDecoderConfigurationOptions',
        'VideoDecodingCapabilities' => 'ONVIF::Device::Types::VideoDecoderConfigurationOptions',
    },
    {

        'AudioEncodingCapabilities' => 'AudioEncodingCapabilities',
        'AudioDecodingCapabilities' => 'AudioDecodingCapabilities',
        'VideoDecodingCapabilities' => 'VideoDecodingCapabilities',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::Device::Types::CodingCapabilities

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
CodingCapabilities from the namespace http://www.onvif.org/ver10/schema.

This type contains the Audio and Video coding capabilities of a display service.




=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * AudioEncodingCapabilities


=item * AudioDecodingCapabilities


=item * VideoDecodingCapabilities




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Device::Types::CodingCapabilities
   AudioEncodingCapabilities =>  { # ONVIF::Device::Types::AudioEncoderConfigurationOptions
     Options =>  { # ONVIF::Device::Types::AudioEncoderConfigurationOption
       Encoding => $some_value, # AudioEncoding
       BitrateList =>  { # ONVIF::Device::Types::IntList
         Items =>  $some_value, # int
       },
       SampleRateList =>  { # ONVIF::Device::Types::IntList
         Items =>  $some_value, # int
       },
     },
   },
   AudioDecodingCapabilities =>  { # ONVIF::Device::Types::AudioDecoderConfigurationOptions
     AACDecOptions =>  { # ONVIF::Device::Types::AACDecOptions
       Bitrate =>  { # ONVIF::Device::Types::IntList
         Items =>  $some_value, # int
       },
       SampleRateRange =>  { # ONVIF::Device::Types::IntList
         Items =>  $some_value, # int
       },
     },
     G711DecOptions =>  { # ONVIF::Device::Types::G711DecOptions
       Bitrate =>  { # ONVIF::Device::Types::IntList
         Items =>  $some_value, # int
       },
       SampleRateRange =>  { # ONVIF::Device::Types::IntList
         Items =>  $some_value, # int
       },
     },
     G726DecOptions =>  { # ONVIF::Device::Types::G726DecOptions
       Bitrate =>  { # ONVIF::Device::Types::IntList
         Items =>  $some_value, # int
       },
       SampleRateRange =>  { # ONVIF::Device::Types::IntList
         Items =>  $some_value, # int
       },
     },
     Extension =>  { # ONVIF::Device::Types::AudioDecoderConfigurationOptionsExtension
     },
   },
   VideoDecodingCapabilities =>  { # ONVIF::Device::Types::VideoDecoderConfigurationOptions
     JpegDecOptions =>  { # ONVIF::Device::Types::JpegDecOptions
       ResolutionsAvailable =>  { # ONVIF::Device::Types::VideoResolution
         Width =>  $some_value, # int
         Height =>  $some_value, # int
       },
       SupportedInputBitrate =>  { # ONVIF::Device::Types::IntRange
         Min =>  $some_value, # int
         Max =>  $some_value, # int
       },
       SupportedFrameRate =>  { # ONVIF::Device::Types::IntRange
         Min =>  $some_value, # int
         Max =>  $some_value, # int
       },
     },
     H264DecOptions =>  { # ONVIF::Device::Types::H264DecOptions
       ResolutionsAvailable =>  { # ONVIF::Device::Types::VideoResolution
         Width =>  $some_value, # int
         Height =>  $some_value, # int
       },
       SupportedH264Profiles => $some_value, # H264Profile
       SupportedInputBitrate =>  { # ONVIF::Device::Types::IntRange
         Min =>  $some_value, # int
         Max =>  $some_value, # int
       },
       SupportedFrameRate =>  { # ONVIF::Device::Types::IntRange
         Min =>  $some_value, # int
         Max =>  $some_value, # int
       },
     },
     Mpeg4DecOptions =>  { # ONVIF::Device::Types::Mpeg4DecOptions
       ResolutionsAvailable =>  { # ONVIF::Device::Types::VideoResolution
         Width =>  $some_value, # int
         Height =>  $some_value, # int
       },
       SupportedMpeg4Profiles => $some_value, # Mpeg4Profile
       SupportedInputBitrate =>  { # ONVIF::Device::Types::IntRange
         Min =>  $some_value, # int
         Max =>  $some_value, # int
       },
       SupportedFrameRate =>  { # ONVIF::Device::Types::IntRange
         Min =>  $some_value, # int
         Max =>  $some_value, # int
       },
     },
     Extension =>  { # ONVIF::Device::Types::VideoDecoderConfigurationOptionsExtension
     },
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

