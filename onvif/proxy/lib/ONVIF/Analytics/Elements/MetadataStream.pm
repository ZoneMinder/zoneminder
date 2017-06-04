
package ONVIF::Analytics::Elements::MetadataStream;
use strict;
use warnings;

{ # BLOCK to scope variables

sub get_xmlns { 'http://www.onvif.org/ver10/schema' }

__PACKAGE__->__set_name('MetadataStream');
__PACKAGE__->__set_nillable();
__PACKAGE__->__set_minOccurs();
__PACKAGE__->__set_maxOccurs();
__PACKAGE__->__set_ref();
use base qw(
    SOAP::WSDL::XSD::Typelib::Element
    ONVIF::Analytics::Types::MetadataStream
);

}

1;


=pod

=head1 NAME

ONVIF::Analytics::Elements::MetadataStream

=head1 DESCRIPTION

Perl data type class for the XML Schema defined element
MetadataStream from the namespace http://www.onvif.org/ver10/schema.







=head1 METHODS

=head2 new

 my $element = ONVIF::Analytics::Elements::MetadataStream->new($data);

Constructor. The following data structure may be passed to new():

 { # ONVIF::Analytics::Types::MetadataStream
   # One of the following elements.
   # No occurrence checks yet, so be sure to pass just one...
   VideoAnalytics =>    { # ONVIF::Analytics::Types::VideoAnalyticsStream
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
   PTZ =>    { # ONVIF::Analytics::Types::PTZStream
     # One of the following elements.
     # No occurrence checks yet, so be sure to pass just one...
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
     Extension =>  { # ONVIF::Analytics::Types::PTZStreamExtension
     },
   },
   Event =>  $some_value, # anyType
   Extension =>  { # ONVIF::Analytics::Types::MetadataStreamExtension
     AudioAnalyticsStream =>  { # ONVIF::Analytics::Types::AudioAnalyticsStream
       AudioDescriptor =>  { # ONVIF::Analytics::Types::AudioDescriptor
       },
       Extension =>  { # ONVIF::Analytics::Types::AudioAnalyticsStreamExtension
       },
     },
     Extension =>  { # ONVIF::Analytics::Types::MetadataStreamExtension2
     },
   },
 },

=head1 AUTHOR

Generated by SOAP::WSDL

=cut

