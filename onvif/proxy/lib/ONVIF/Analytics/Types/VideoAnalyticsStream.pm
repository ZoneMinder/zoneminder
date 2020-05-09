package ONVIF::Analytics::Types::VideoAnalyticsStream;
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

my %Frame_of :ATTR(:get<Frame>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        Frame
        Extension

    ) ],
    {
        'Frame' => \%Frame_of,
        'Extension' => \%Extension_of,
    },
    {
        'Frame' => 'ONVIF::Analytics::Types::Frame',
        'Extension' => 'ONVIF::Analytics::Types::VideoAnalyticsStreamExtension',
    },
    {

        'Frame' => 'Frame',
        'Extension' => 'Extension',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::Analytics::Types::VideoAnalyticsStream

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
VideoAnalyticsStream from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * Frame


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Analytics::Types::VideoAnalyticsStream
   # One of the following elements.
   # No occurrence checks yet, so be sure to pass just one...
   Frame =>  { # ONVIF::Analytics::Types::Frame
     PTZStatus =>  { # ONVIF::Analytics::Types::PTZStatus
       Position =>  { # ONVIF::Analytics::Types::PTZVector
         PanTilt => ,
         Zoom => ,
       },
       MoveStatus =>  { # ONVIF::Analytics::Types::PTZMoveStatus
         PanTilt => $some_value, # MoveStatus
         Zoom => $some_value, # MoveStatus
       },
       Error =>  $some_value, # string
       UtcTime =>  $some_value, # dateTime
     },
     Transformation =>  { # ONVIF::Analytics::Types::Transformation
       Translate => ,
       Scale => ,
       Extension =>  { # ONVIF::Analytics::Types::TransformationExtension
       },
     },
     Object =>  { # ONVIF::Analytics::Types::Object
       Appearance =>  { # ONVIF::Analytics::Types::Appearance
         Transformation =>  { # ONVIF::Analytics::Types::Transformation
           Translate => ,
           Scale => ,
           Extension =>  { # ONVIF::Analytics::Types::TransformationExtension
           },
         },
         Shape =>  { # ONVIF::Analytics::Types::ShapeDescriptor
           BoundingBox => ,
           CenterOfGravity => ,
           Polygon =>  { # ONVIF::Analytics::Types::Polygon
             Point => ,
           },
           Extension =>  { # ONVIF::Analytics::Types::ShapeDescriptorExtension
           },
         },
         Color =>  { # ONVIF::Analytics::Types::ColorDescriptor
           ColorCluster =>  {
             Color => ,
             Weight =>  $some_value, # float
             Covariance => ,
           },
           Extension =>  { # ONVIF::Analytics::Types::ColorDescriptorExtension
           },
         },
         Class =>  { # ONVIF::Analytics::Types::ClassDescriptor
           ClassCandidate =>  {
             Type => $some_value, # ClassType
             Likelihood =>  $some_value, # float
           },
           Extension =>  { # ONVIF::Analytics::Types::ClassDescriptorExtension
             OtherTypes =>  { # ONVIF::Analytics::Types::OtherType
               Type =>  $some_value, # string
               Likelihood =>  $some_value, # float
             },
             Extension =>  { # ONVIF::Analytics::Types::ClassDescriptorExtension2
             },
           },
         },
         Extension =>  { # ONVIF::Analytics::Types::AppearanceExtension
         },
       },
       Behaviour =>  { # ONVIF::Analytics::Types::Behaviour
         Removed =>  {
         },
         Idle =>  {
         },
         Extension =>  { # ONVIF::Analytics::Types::BehaviourExtension
         },
       },
       Extension =>  { # ONVIF::Analytics::Types::ObjectExtension
       },
     },
     ObjectTree =>  { # ONVIF::Analytics::Types::ObjectTree
       Rename =>  { # ONVIF::Analytics::Types::Rename
         from => ,
         to => ,
       },
       Split =>  { # ONVIF::Analytics::Types::Split
         from => ,
         to => ,
       },
       Merge =>  { # ONVIF::Analytics::Types::Merge
         from => ,
         to => ,
       },
       Delete => ,
       Extension =>  { # ONVIF::Analytics::Types::ObjectTreeExtension
       },
     },
     Extension =>  { # ONVIF::Analytics::Types::FrameExtension
       MotionInCells =>  { # ONVIF::Analytics::Types::MotionInCells
       },
       Extension =>  { # ONVIF::Analytics::Types::FrameExtension2
       },
     },
   },
   Extension =>  { # ONVIF::Analytics::Types::VideoAnalyticsStreamExtension
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

