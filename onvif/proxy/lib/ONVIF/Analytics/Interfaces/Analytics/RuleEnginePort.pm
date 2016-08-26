package ONVIF::Analytics::Interfaces::Analytics::RuleEnginePort;
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

sub GetSupportedRules {
    my ($self, $body, $header) = @_;
    die "GetSupportedRules must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetSupportedRules',
        soap_action => 'http://www.onvif.org/ver20/analytics/wsdl/GetSupportedRules',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Analytics::Elements::GetSupportedRules )],

        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub CreateRules {
    my ($self, $body, $header) = @_;
    die "CreateRules must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'CreateRules',
        soap_action => 'http://www.onvif.org/ver20/analytics/wsdl/CreateRules',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Analytics::Elements::CreateRules )],

        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub DeleteRules {
    my ($self, $body, $header) = @_;
    die "DeleteRules must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'DeleteRules',
        soap_action => 'http://www.onvif.org/ver20/analytics/wsdl/DeleteRules',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Analytics::Elements::DeleteRules )],

        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetRules {
    my ($self, $body, $header) = @_;
    die "GetRules must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetRules',
        soap_action => 'http://www.onvif.org/ver20/analytics/wsdl/GetRules',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Analytics::Elements::GetRules )],

        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub ModifyRules {
    my ($self, $body, $header) = @_;
    die "ModifyRules must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'ModifyRules',
        soap_action => 'http://www.onvif.org/ver20/analytics/wsdl/ModifyRules',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Analytics::Elements::ModifyRules )],

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

ONVIF::Analytics::Interfaces::Analytics::RuleEnginePort - SOAP Interface for the Analytics Web Service

=head1 SYNOPSIS

 use ONVIF::Analytics::Interfaces::Analytics::RuleEnginePort;
 my $interface = ONVIF::Analytics::Interfaces::Analytics::RuleEnginePort->new();

 my $response;
 $response = $interface->GetSupportedRules();
 $response = $interface->CreateRules();
 $response = $interface->DeleteRules();
 $response = $interface->GetRules();
 $response = $interface->ModifyRules();



=head1 DESCRIPTION

SOAP Interface for the Analytics web service
located at http://www.examples.com/Analytics/.

=head1 SERVICE Analytics



=head2 Port RuleEnginePort



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



=head3 GetSupportedRules

List all rules that are supported by the given VideoAnalyticsConfiguration. The result of this method may depend on the overall Video analytics configuration of the device, which is available via the current set of profiles. 

Returns a L<ONVIF::Analytics::Elements::GetSupportedRulesResponse|ONVIF::Analytics::Elements::GetSupportedRulesResponse> object.

 $response = $interface->GetSupportedRules( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 CreateRules

GetCompatibleVideoAnalyticsConfigurations. 

Returns a L<ONVIF::Analytics::Elements::CreateRulesResponse|ONVIF::Analytics::Elements::CreateRulesResponse> object.

 $response = $interface->CreateRules( {
    ConfigurationToken => $some_value, # ReferenceToken
    Rule =>  { # ONVIF::Analytics::Types::Config
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

=head3 DeleteRules

Remove one or more rules from a VideoAnalyticsConfiguration. 

Returns a L<ONVIF::Analytics::Elements::DeleteRulesResponse|ONVIF::Analytics::Elements::DeleteRulesResponse> object.

 $response = $interface->DeleteRules( {
    ConfigurationToken => $some_value, # ReferenceToken
    RuleName =>  $some_value, # string
  },,
 );

=head3 GetRules

List the currently assigned set of rules of a VideoAnalyticsConfiguration. 

Returns a L<ONVIF::Analytics::Elements::GetRulesResponse|ONVIF::Analytics::Elements::GetRulesResponse> object.

 $response = $interface->GetRules( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 ModifyRules

Modify one or more rules of a VideoAnalyticsConfiguration. The rules are referenced by their names. 

Returns a L<ONVIF::Analytics::Elements::ModifyRulesResponse|ONVIF::Analytics::Elements::ModifyRulesResponse> object.

 $response = $interface->ModifyRules( {
    ConfigurationToken => $some_value, # ReferenceToken
    Rule =>  { # ONVIF::Analytics::Types::Config
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
