
package ONVIF::PTZ::Elements::MetadataStream;
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
    ONVIF::PTZ::Types::MetadataStream
);

}

1;


=pod

=head1 NAME

ONVIF::PTZ::Elements::MetadataStream

=head1 DESCRIPTION

Perl data type class for the XML Schema defined element
MetadataStream from the namespace http://www.onvif.org/ver10/schema.







=head1 METHODS

=head2 new

 my $element = ONVIF::PTZ::Elements::MetadataStream->new($data);

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

