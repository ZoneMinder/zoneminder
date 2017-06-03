package ONVIF::Device::Types::MetadataStream;
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

my %VideoAnalytics_of :ATTR(:get<VideoAnalytics>);
my %PTZ_of :ATTR(:get<PTZ>);
my %Event_of :ATTR(:get<Event>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        VideoAnalytics
        PTZ
        Event
        Extension

    ) ],
    {
        'VideoAnalytics' => \%VideoAnalytics_of,
        'PTZ' => \%PTZ_of,
        'Event' => \%Event_of,
        'Extension' => \%Extension_of,
    },
    {
        'VideoAnalytics' => 'ONVIF::Device::Types::VideoAnalyticsStream',
        'PTZ' => 'ONVIF::Device::Types::PTZStream',
        'Event' => 'SOAP::WSDL::XSD::Typelib::Builtin::anyType',
        'Extension' => 'ONVIF::Device::Types::MetadataStreamExtension',
    },
    {

        'VideoAnalytics' => 'VideoAnalytics',
        'PTZ' => 'PTZ',
        'Event' => 'Event',
        'Extension' => 'Extension',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::Device::Types::MetadataStream

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
MetadataStream from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * VideoAnalytics


=item * PTZ


=item * Event


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Device::Types::MetadataStream
   # One of the following elements.
   # No occurrence checks yet, so be sure to pass just one...
   VideoAnalytics =>    { # ONVIF::Device::Types::VideoAnalyticsStream
     # One of the following elements.
     # No occurrence checks yet, so be sure to pass just one...
     Frame =>  { # ONVIF::Device::Types::Frame
       PTZStatus =>  { # ONVIF::Device::Types::PTZStatus
         Position =>  { # ONVIF::Device::Types::PTZVector
           PanTilt => ,
           Zoom => ,
         },
         MoveStatus =>  { # ONVIF::Device::Types::PTZMoveStatus
           PanTilt => $some_value, # MoveStatus
           Zoom => $some_value, # MoveStatus
         },
         Error =>  $some_value, # string
         UtcTime =>  $some_value, # dateTime
       },
       Transformation =>  { # ONVIF::Device::Types::Transformation
         Translate => ,
         Scale => ,
         Extension =>  { # ONVIF::Device::Types::TransformationExtension
         },
       },
       Object =>  { # ONVIF::Device::Types::Object
         Appearance =>  { # ONVIF::Device::Types::Appearance
           Transformation =>  { # ONVIF::Device::Types::Transformation
             Translate => ,
             Scale => ,
             Extension =>  { # ONVIF::Device::Types::TransformationExtension
             },
           },
           Shape =>  { # ONVIF::Device::Types::ShapeDescriptor
             BoundingBox => ,
             CenterOfGravity => ,
             Polygon =>  { # ONVIF::Device::Types::Polygon
               Point => ,
             },
             Extension =>  { # ONVIF::Device::Types::ShapeDescriptorExtension
             },
           },
           Color =>  { # ONVIF::Device::Types::ColorDescriptor
             ColorCluster =>  {
               Color => ,
               Weight =>  $some_value, # float
               Covariance => ,
             },
             Extension =>  { # ONVIF::Device::Types::ColorDescriptorExtension
             },
           },
           Class =>  { # ONVIF::Device::Types::ClassDescriptor
             ClassCandidate =>  {
               Type => $some_value, # ClassType
               Likelihood =>  $some_value, # float
             },
             Extension =>  { # ONVIF::Device::Types::ClassDescriptorExtension
               OtherTypes =>  { # ONVIF::Device::Types::OtherType
                 Type =>  $some_value, # string
                 Likelihood =>  $some_value, # float
               },
               Extension =>  { # ONVIF::Device::Types::ClassDescriptorExtension2
               },
             },
           },
           Extension =>  { # ONVIF::Device::Types::AppearanceExtension
           },
         },
         Behaviour =>  { # ONVIF::Device::Types::Behaviour
           Removed =>  {
           },
           Idle =>  {
           },
           Extension =>  { # ONVIF::Device::Types::BehaviourExtension
           },
         },
         Extension =>  { # ONVIF::Device::Types::ObjectExtension
         },
       },
       ObjectTree =>  { # ONVIF::Device::Types::ObjectTree
         Rename =>  { # ONVIF::Device::Types::Rename
           from => ,
           to => ,
         },
         Split =>  { # ONVIF::Device::Types::Split
           from => ,
           to => ,
         },
         Merge =>  { # ONVIF::Device::Types::Merge
           from => ,
           to => ,
         },
         Delete => ,
         Extension =>  { # ONVIF::Device::Types::ObjectTreeExtension
         },
       },
       Extension =>  { # ONVIF::Device::Types::FrameExtension
         MotionInCells =>  { # ONVIF::Device::Types::MotionInCells
         },
         Extension =>  { # ONVIF::Device::Types::FrameExtension2
         },
       },
     },
     Extension =>  { # ONVIF::Device::Types::VideoAnalyticsStreamExtension
     },
   },
   PTZ =>    { # ONVIF::Device::Types::PTZStream
     # One of the following elements.
     # No occurrence checks yet, so be sure to pass just one...
     PTZStatus =>  { # ONVIF::Device::Types::PTZStatus
       Position =>  { # ONVIF::Device::Types::PTZVector
         PanTilt => ,
         Zoom => ,
       },
       MoveStatus =>  { # ONVIF::Device::Types::PTZMoveStatus
         PanTilt => $some_value, # MoveStatus
         Zoom => $some_value, # MoveStatus
       },
       Error =>  $some_value, # string
       UtcTime =>  $some_value, # dateTime
     },
     Extension =>  { # ONVIF::Device::Types::PTZStreamExtension
     },
   },
   Event =>  $some_value, # anyType
   Extension =>  { # ONVIF::Device::Types::MetadataStreamExtension
     AudioAnalyticsStream =>  { # ONVIF::Device::Types::AudioAnalyticsStream
       AudioDescriptor =>  { # ONVIF::Device::Types::AudioDescriptor
       },
       Extension =>  { # ONVIF::Device::Types::AudioAnalyticsStreamExtension
       },
     },
     Extension =>  { # ONVIF::Device::Types::MetadataStreamExtension2
     },
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

