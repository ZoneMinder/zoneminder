package ONVIF::Media::Types::MetadataStream;
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
        'VideoAnalytics' => 'ONVIF::Media::Types::VideoAnalyticsStream',
        'PTZ' => 'ONVIF::Media::Types::PTZStream',
        'Event' => 'SOAP::WSDL::XSD::Typelib::Builtin::anyType',
        'Extension' => 'ONVIF::Media::Types::MetadataStreamExtension',
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

ONVIF::Media::Types::MetadataStream

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

 { # ONVIF::Media::Types::MetadataStream
   # One of the following elements.
   # No occurrence checks yet, so be sure to pass just one...
   VideoAnalytics =>    { # ONVIF::Media::Types::VideoAnalyticsStream
     # One of the following elements.
     # No occurrence checks yet, so be sure to pass just one...
     Frame =>  { # ONVIF::Media::Types::Frame
       PTZStatus =>  { # ONVIF::Media::Types::PTZStatus
         Position =>  { # ONVIF::Media::Types::PTZVector
           PanTilt => ,
           Zoom => ,
         },
         MoveStatus =>  { # ONVIF::Media::Types::PTZMoveStatus
           PanTilt => $some_value, # MoveStatus
           Zoom => $some_value, # MoveStatus
         },
         Error =>  $some_value, # string
         UtcTime =>  $some_value, # dateTime
       },
       Transformation =>  { # ONVIF::Media::Types::Transformation
         Translate => ,
         Scale => ,
         Extension =>  { # ONVIF::Media::Types::TransformationExtension
         },
       },
       Object =>  { # ONVIF::Media::Types::Object
         Appearance =>  { # ONVIF::Media::Types::Appearance
           Transformation =>  { # ONVIF::Media::Types::Transformation
             Translate => ,
             Scale => ,
             Extension =>  { # ONVIF::Media::Types::TransformationExtension
             },
           },
           Shape =>  { # ONVIF::Media::Types::ShapeDescriptor
             BoundingBox => ,
             CenterOfGravity => ,
             Polygon =>  { # ONVIF::Media::Types::Polygon
               Point => ,
             },
             Extension =>  { # ONVIF::Media::Types::ShapeDescriptorExtension
             },
           },
           Color =>  { # ONVIF::Media::Types::ColorDescriptor
             ColorCluster =>  {
               Color => ,
               Weight =>  $some_value, # float
               Covariance => ,
             },
             Extension =>  { # ONVIF::Media::Types::ColorDescriptorExtension
             },
           },
           Class =>  { # ONVIF::Media::Types::ClassDescriptor
             ClassCandidate =>  {
               Type => $some_value, # ClassType
               Likelihood =>  $some_value, # float
             },
             Extension =>  { # ONVIF::Media::Types::ClassDescriptorExtension
               OtherTypes =>  { # ONVIF::Media::Types::OtherType
                 Type =>  $some_value, # string
                 Likelihood =>  $some_value, # float
               },
               Extension =>  { # ONVIF::Media::Types::ClassDescriptorExtension2
               },
             },
           },
           Extension =>  { # ONVIF::Media::Types::AppearanceExtension
           },
         },
         Behaviour =>  { # ONVIF::Media::Types::Behaviour
           Removed =>  {
           },
           Idle =>  {
           },
           Extension =>  { # ONVIF::Media::Types::BehaviourExtension
           },
         },
         Extension =>  { # ONVIF::Media::Types::ObjectExtension
         },
       },
       ObjectTree =>  { # ONVIF::Media::Types::ObjectTree
         Rename =>  { # ONVIF::Media::Types::Rename
           from => ,
           to => ,
         },
         Split =>  { # ONVIF::Media::Types::Split
           from => ,
           to => ,
         },
         Merge =>  { # ONVIF::Media::Types::Merge
           from => ,
           to => ,
         },
         Delete => ,
         Extension =>  { # ONVIF::Media::Types::ObjectTreeExtension
         },
       },
       Extension =>  { # ONVIF::Media::Types::FrameExtension
         MotionInCells =>  { # ONVIF::Media::Types::MotionInCells
         },
         Extension =>  { # ONVIF::Media::Types::FrameExtension2
         },
       },
     },
     Extension =>  { # ONVIF::Media::Types::VideoAnalyticsStreamExtension
     },
   },
   PTZ =>    { # ONVIF::Media::Types::PTZStream
     # One of the following elements.
     # No occurrence checks yet, so be sure to pass just one...
     PTZStatus =>  { # ONVIF::Media::Types::PTZStatus
       Position =>  { # ONVIF::Media::Types::PTZVector
         PanTilt => ,
         Zoom => ,
       },
       MoveStatus =>  { # ONVIF::Media::Types::PTZMoveStatus
         PanTilt => $some_value, # MoveStatus
         Zoom => $some_value, # MoveStatus
       },
       Error =>  $some_value, # string
       UtcTime =>  $some_value, # dateTime
     },
     Extension =>  { # ONVIF::Media::Types::PTZStreamExtension
     },
   },
   Event =>  $some_value, # anyType
   Extension =>  { # ONVIF::Media::Types::MetadataStreamExtension
     AudioAnalyticsStream =>  { # ONVIF::Media::Types::AudioAnalyticsStream
       AudioDescriptor =>  { # ONVIF::Media::Types::AudioDescriptor
       },
       Extension =>  { # ONVIF::Media::Types::AudioAnalyticsStreamExtension
       },
     },
     Extension =>  { # ONVIF::Media::Types::MetadataStreamExtension2
     },
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

