package ONVIF::Analytics::Interfaces::Analytics::AnalyticsEnginePort;
use strict;
use warnings;
use Class::Std::Fast::Storable;
use Scalar::Util qw(blessed);
use base qw(SOAP::WSDL::Client::Base);

# only load if it hasn't been loaded before
require ONVIF::Analytics::Typemaps::Analytics
    if not ONVIF::Analytics::Typemaps::Analytics->can('get_class');

sub START {
    $_[0]->set_proxy('http://www.examples.com/Analytics/') if not $_[2]->{proxy};
    $_[0]->set_class_resolver('ONVIF::Analytics::Typemaps::Analytics')
        if not $_[2]->{class_resolver};

    $_[0]->set_prefix($_[2]->{use_prefix}) if exists $_[2]->{use_prefix};
}

sub GetServiceCapabilities {
    my ($self, $body, $header) = @_;
    die "GetServiceCapabilities must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetServiceCapabilities',
        soap_action => 'http://www.onvif.org/ver20/analytics/wsdl/GetServiceCapabilities',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Analytics::Elements::GetServiceCapabilities )],

        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetSupportedAnalyticsModules {
    my ($self, $body, $header) = @_;
    die "GetSupportedAnalyticsModules must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetSupportedAnalyticsModules',
        soap_action => 'http://www.onvif.org/ver20/analytics/wsdl/GetSupportedAnalyticsModules',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Analytics::Elements::GetSupportedAnalyticsModules )],

        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub CreateAnalyticsModules {
    my ($self, $body, $header) = @_;
    die "CreateAnalyticsModules must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'CreateAnalyticsModules',
        soap_action => 'http://www.onvif.org/ver20/analytics/wsdl/CreateAnalyticsModules',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Analytics::Elements::CreateAnalyticsModules )],

        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub DeleteAnalyticsModules {
    my ($self, $body, $header) = @_;
    die "DeleteAnalyticsModules must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'DeleteAnalyticsModules',
        soap_action => 'http://www.onvif.org/ver20/analytics/wsdl/DeleteAnalyticsModules',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Analytics::Elements::DeleteAnalyticsModules )],

        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAnalyticsModules {
    my ($self, $body, $header) = @_;
    die "GetAnalyticsModules must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAnalyticsModules',
        soap_action => 'http://www.onvif.org/ver20/analytics/wsdl/GetAnalyticsModules',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Analytics::Elements::GetAnalyticsModules )],

        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub ModifyAnalyticsModules {
    my ($self, $body, $header) = @_;
    die "ModifyAnalyticsModules must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'ModifyAnalyticsModules',
        soap_action => 'http://www.onvif.org/ver20/analytics/wsdl/ModifyAnalyticsModules',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Analytics::Elements::ModifyAnalyticsModules )],

        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}




1;



__END__

=pod

=head1 NAME

ONVIF::Analytics::Interfaces::Analytics::AnalyticsEnginePort - SOAP Interface for the Analytics Web Service

=head1 SYNOPSIS

 use ONVIF::Analytics::Interfaces::Analytics::AnalyticsEnginePort;
 my $interface = ONVIF::Analytics::Interfaces::Analytics::AnalyticsEnginePort->new();

 my $response;
 $response = $interface->GetServiceCapabilities();
 $response = $interface->GetSupportedAnalyticsModules();
 $response = $interface->CreateAnalyticsModules();
 $response = $interface->DeleteAnalyticsModules();
 $response = $interface->GetAnalyticsModules();
 $response = $interface->ModifyAnalyticsModules();



=head1 DESCRIPTION

SOAP Interface for the Analytics web service
located at http://www.examples.com/Analytics/.

=head1 SERVICE Analytics



=head2 Port AnalyticsEnginePort



=head1 METHODS

=head2 General methods

=head3 new

Constructor.

All arguments are forwarded to L<SOAP::WSDL::Client|SOAP::WSDL::Client>.

=head2 SOAP Service methods

Method synopsis is displayed with hash refs as parameters.

The commented class names in the method's parameters denote that objects
of the corresponding class can be passed instead of the marked hash ref.

You may pass any combination of objects, hash and list refs to these
methods, as long as you meet the structure.

List items (i.e. multiple occurrences) are not displayed in the synopsis.
You may generally pass a list ref of hash refs (or objects) instead of a hash
ref - this may result in invalid XML if used improperly, though. Note that
SOAP::WSDL always expects list references at maximum depth position.

XML attributes are not displayed in this synopsis and cannot be set using
hash refs. See the respective class' documentation for additional information.



=head3 GetServiceCapabilities

Returns the capabilities of the analytics service. The result is returned in a typed answer.

Returns a L<ONVIF::Analytics::Elements::GetServiceCapabilitiesResponse|ONVIF::Analytics::Elements::GetServiceCapabilitiesResponse> object.

 $response = $interface->GetServiceCapabilities( {
  },,
 );

=head3 GetSupportedAnalyticsModules

List all analytics modules that are supported by the given VideoAnalyticsConfiguration. The result of this method may depend on the overall Video analytics configuration of the device, which is available via the current set of profiles. 

Returns a L<ONVIF::Analytics::Elements::GetSupportedAnalyticsModulesResponse|ONVIF::Analytics::Elements::GetSupportedAnalyticsModulesResponse> object.

 $response = $interface->GetSupportedAnalyticsModules( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 CreateAnalyticsModules

The device shall ensure that a corresponding analytics engine starts operation when a client subscribes directly or indirectly for events produced by the analytics or rule engine or when a client requests the corresponding scene description stream. An analytics module must be attached to a Video source using the media profiles before it can be used. In case differing analytics configurations are attached to the same profile it is undefined which of the analytics module configuration becomes active if no stream is activated or multiple streams with different profiles are activated at the same time. 

Returns a L<ONVIF::Analytics::Elements::CreateAnalyticsModulesResponse|ONVIF::Analytics::Elements::CreateAnalyticsModulesResponse> object.

 $response = $interface->CreateAnalyticsModules( {
    ConfigurationToken => $some_value, # ReferenceToken
    AnalyticsModule =>  { # ONVIF::Analytics::Types::Config
      Parameters =>  { # ONVIF::Analytics::Types::ItemList
        SimpleItem => ,
        ElementItem =>  {
        },
        Extension =>  { # ONVIF::Analytics::Types::ItemListExtension
        },
      },
    },
  },,
 );

=head3 DeleteAnalyticsModules

 

Returns a L<ONVIF::Analytics::Elements::DeleteAnalyticsModulesResponse|ONVIF::Analytics::Elements::DeleteAnalyticsModulesResponse> object.

 $response = $interface->DeleteAnalyticsModules( {
    ConfigurationToken => $some_value, # ReferenceToken
    AnalyticsModuleName =>  $some_value, # string
  },,
 );

=head3 GetAnalyticsModules

List the currently assigned set of analytics modules of a VideoAnalyticsConfiguration. 

Returns a L<ONVIF::Analytics::Elements::GetAnalyticsModulesResponse|ONVIF::Analytics::Elements::GetAnalyticsModulesResponse> object.

 $response = $interface->GetAnalyticsModules( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 ModifyAnalyticsModules

Modify the settings of one or more analytics modules of a VideoAnalyticsConfiguration. The modules are referenced by their names. It is allowed to pass only a subset to be modified. 

Returns a L<ONVIF::Analytics::Elements::ModifyAnalyticsModulesResponse|ONVIF::Analytics::Elements::ModifyAnalyticsModulesResponse> object.

 $response = $interface->ModifyAnalyticsModules( {
    ConfigurationToken => $some_value, # ReferenceToken
    AnalyticsModule =>  { # ONVIF::Analytics::Types::Config
      Parameters =>  { # ONVIF::Analytics::Types::ItemList
        SimpleItem => ,
        ElementItem =>  {
        },
        Extension =>  { # ONVIF::Analytics::Types::ItemListExtension
        },
      },
    },
  },,
 );



=head1 AUTHOR

Generated by SOAP::WSDL on Tue Jul 15 19:19:50 2014

=cut
