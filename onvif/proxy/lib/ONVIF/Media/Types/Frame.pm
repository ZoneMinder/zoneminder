package ONVIF::Media::Types::Frame;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/schema' };

our $XML_ATTRIBUTE_CLASS = 'ONVIF::Media::Types::Frame::_Frame::XmlAttr';

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}

use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

Class::Std::initialize();

{ # BLOCK to scope variables

my %PTZStatus_of :ATTR(:get<PTZStatus>);
my %Transformation_of :ATTR(:get<Transformation>);
my %Object_of :ATTR(:get<Object>);
my %ObjectTree_of :ATTR(:get<ObjectTree>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        PTZStatus
        Transformation
        Object
        ObjectTree
        Extension

    ) ],
    {
        'PTZStatus' => \%PTZStatus_of,
        'Transformation' => \%Transformation_of,
        'Object' => \%Object_of,
        'ObjectTree' => \%ObjectTree_of,
        'Extension' => \%Extension_of,
    },
    {
        'PTZStatus' => 'ONVIF::Media::Types::PTZStatus',
        'Transformation' => 'ONVIF::Media::Types::Transformation',
        'Object' => 'ONVIF::Media::Types::Object',
        'ObjectTree' => 'ONVIF::Media::Types::ObjectTree',
        'Extension' => 'ONVIF::Media::Types::FrameExtension',
    },
    {

        'PTZStatus' => 'PTZStatus',
        'Transformation' => 'Transformation',
        'Object' => 'Object',
        'ObjectTree' => 'ObjectTree',
        'Extension' => 'Extension',
    }
);

} # end BLOCK




package ONVIF::Media::Types::Frame::_Frame::XmlAttr;
use base qw(SOAP::WSDL::XSD::Typelib::AttributeSet);

{ # BLOCK to scope variables

my %UtcTime_of :ATTR(:get<UtcTime>);

__PACKAGE__->_factory(
    [ qw(
        UtcTime
    ) ],
    {

        UtcTime => \%UtcTime_of,
    },
    {
        UtcTime => 'SOAP::WSDL::XSD::Typelib::Builtin::dateTime',
    }
);

} # end BLOCK




1;


=pod

=head1 NAME

ONVIF::Media::Types::Frame

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
Frame from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * PTZStatus


=item * Transformation


=item * Object


=item * ObjectTree


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Media::Types::Frame
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



=head2 attr

NOTE: Attribute documentation is experimental, and may be inaccurate.
See the correspondent WSDL/XML Schema if in question.

This class has additional attributes, accessibly via the C<attr()> method.

attr() returns an object of the class ONVIF::Media::Types::Frame::_Frame::XmlAttr.

The following attributes can be accessed on this object via the corresponding
get_/set_ methods:

=over

=item * UtcTime



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::dateTime|SOAP::WSDL::XSD::Typelib::Builtin::dateTime>.


=back




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

