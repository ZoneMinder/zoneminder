package ONVIF::PTZ::Types::MetadataStream;
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
        'VideoAnalytics' => 'ONVIF::PTZ::Types::VideoAnalyticsStream',
        'PTZ' => 'ONVIF::PTZ::Types::PTZStream',
        'Event' => 'SOAP::WSDL::XSD::Typelib::Builtin::anyType',
        'Extension' => 'ONVIF::PTZ::Types::MetadataStreamExtension',
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

ONVIF::PTZ::Types::MetadataStream

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

 { # ONVIF::PTZ::Types::MetadataStream
   # One of the following elements.
   # No occurrence checks yet, so be sure to pass just one...
   VideoAnalytics =>    { # ONVIF::PTZ::Types::VideoAnalyticsStream
     # One of the following elements.
     # No occurrence checks yet, so be sure to pass just one...
     Frame =>  { # ONVIF::PTZ::Types::Frame
       PTZStatus =>  { # ONVIF::PTZ::Types::PTZStatus
         Position =>  { # ONVIF::PTZ::Types::PTZVector
           PanTilt => ,
           Zoom => ,
         },
         MoveStatus =>  { # ONVIF::PTZ::Types::PTZMoveStatus
           PanTilt => $some_value, # MoveStatus
           Zoom => $some_value, # MoveStatus
         },
         Error =>  $some_value, # string
         UtcTime =>  $some_value, # dateTime
       },
       Transformation =>  { # ONVIF::PTZ::Types::Transformation
         Translate => ,
         Scale => ,
         Extension =>  { # ONVIF::PTZ::Types::TransformationExtension
         },
       },
       Object =>  { # ONVIF::PTZ::Types::Object
         Appearance =>  { # ONVIF::PTZ::Types::Appearance
           Transformation =>  { # ONVIF::PTZ::Types::Transformation
             Translate => ,
             Scale => ,
             Extension =>  { # ONVIF::PTZ::Types::TransformationExtension
             },
           },
           Shape =>  { # ONVIF::PTZ::Types::ShapeDescriptor
             BoundingBox => ,
             CenterOfGravity => ,
             Polygon =>  { # ONVIF::PTZ::Types::Polygon
               Point => ,
             },
             Extension =>  { # ONVIF::PTZ::Types::ShapeDescriptorExtension
             },
           },
           Color =>  { # ONVIF::PTZ::Types::ColorDescriptor
             ColorCluster =>  {
               Color => ,
               Weight =>  $some_value, # float
               Covariance => ,
             },
             Extension =>  { # ONVIF::PTZ::Types::ColorDescriptorExtension
             },
           },
           Class =>  { # ONVIF::PTZ::Types::ClassDescriptor
             ClassCandidate =>  {
               Type => $some_value, # ClassType
               Likelihood =>  $some_value, # float
             },
             Extension =>  { # ONVIF::PTZ::Types::ClassDescriptorExtension
               OtherTypes =>  { # ONVIF::PTZ::Types::OtherType
                 Type =>  $some_value, # string
                 Likelihood =>  $some_value, # float
               },
               Extension =>  { # ONVIF::PTZ::Types::ClassDescriptorExtension2
               },
             },
           },
           Extension =>  { # ONVIF::PTZ::Types::AppearanceExtension
           },
         },
         Behaviour =>  { # ONVIF::PTZ::Types::Behaviour
           Removed =>  {
           },
           Idle =>  {
           },
           Extension =>  { # ONVIF::PTZ::Types::BehaviourExtension
           },
         },
         Extension =>  { # ONVIF::PTZ::Types::ObjectExtension
         },
       },
       ObjectTree =>  { # ONVIF::PTZ::Types::ObjectTree
         Rename =>  { # ONVIF::PTZ::Types::Rename
           from => ,
           to => ,
         },
         Split =>  { # ONVIF::PTZ::Types::Split
           from => ,
           to => ,
         },
         Merge =>  { # ONVIF::PTZ::Types::Merge
           from => ,
           to => ,
         },
         Delete => ,
         Extension =>  { # ONVIF::PTZ::Types::ObjectTreeExtension
         },
       },
       Extension =>  { # ONVIF::PTZ::Types::FrameExtension
         MotionInCells =>  { # ONVIF::PTZ::Types::MotionInCells
         },
         Extension =>  { # ONVIF::PTZ::Types::FrameExtension2
         },
       },
     },
     Extension =>  { # ONVIF::PTZ::Types::VideoAnalyticsStreamExtension
     },
   },
   PTZ =>    { # ONVIF::PTZ::Types::PTZStream
     # One of the following elements.
     # No occurrence checks yet, so be sure to pass just one...
     PTZStatus =>  { # ONVIF::PTZ::Types::PTZStatus
       Position =>  { # ONVIF::PTZ::Types::PTZVector
         PanTilt => ,
         Zoom => ,
       },
       MoveStatus =>  { # ONVIF::PTZ::Types::PTZMoveStatus
         PanTilt => $some_value, # MoveStatus
         Zoom => $some_value, # MoveStatus
       },
       Error =>  $some_value, # string
       UtcTime =>  $some_value, # dateTime
     },
     Extension =>  { # ONVIF::PTZ::Types::PTZStreamExtension
     },
   },
   Event =>  $some_value, # anyType
   Extension =>  { # ONVIF::PTZ::Types::MetadataStreamExtension
     AudioAnalyticsStream =>  { # ONVIF::PTZ::Types::AudioAnalyticsStream
       AudioDescriptor =>  { # ONVIF::PTZ::Types::AudioDescriptor
       },
       Extension =>  { # ONVIF::PTZ::Types::AudioAnalyticsStreamExtension
       },
     },
     Extension =>  { # ONVIF::PTZ::Types::MetadataStreamExtension2
     },
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

