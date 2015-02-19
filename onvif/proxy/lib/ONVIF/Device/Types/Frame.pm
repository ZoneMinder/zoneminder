package ONVIF::Device::Types::Frame;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/schema' };

our $XML_ATTRIBUTE_CLASS = 'ONVIF::Device::Types::Frame::_Frame::XmlAttr';

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
        'PTZStatus' => 'ONVIF::Device::Types::PTZStatus',
        'Transformation' => 'ONVIF::Device::Types::Transformation',
        'Object' => 'ONVIF::Device::Types::Object',
        'ObjectTree' => 'ONVIF::Device::Types::ObjectTree',
        'Extension' => 'ONVIF::Device::Types::FrameExtension',
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




package ONVIF::Device::Types::Frame::_Frame::XmlAttr;
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

ONVIF::Device::Types::Frame

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

 { # ONVIF::Device::Types::Frame
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



=head2 attr

NOTE: Attribute documentation is experimental, and may be inaccurate.
See the correspondent WSDL/XML Schema if in question.

This class has additional attributes, accessibly via the C<attr()> method.

attr() returns an object of the class ONVIF::Device::Types::Frame::_Frame::XmlAttr.

The following attributes can be accessed on this object via the corresponding
get_/set_ methods:

=over

=item * UtcTime



This attribute is of type L<SOAP::WSDL::XSD::Typelib::Builtin::dateTime|SOAP::WSDL::XSD::Typelib::Builtin::dateTime>.


=back




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

