package WSNotification::Interfaces::WSBaseNotificationSender::SubscriptionManagerPort;
use strict;
use warnings;
use Class::Std::Fast::Storable;
use Scalar::Util qw(blessed);
use base qw(SOAP::WSDL::Client::Base);
use Data::Dumper;

# only load if it hasn't been loaded before
require WSNotification::Typemaps::WSBaseNotificationSender
    if not WSNotification::Typemaps::WSBaseNotificationSender->can('get_class');

sub START {
    $_[0]->set_proxy('http://docs.oasis-open.org/wsn/bw-2') if not $_[2]->{proxy};
    $_[0]->set_class_resolver('WSNotification::Typemaps::WSBaseNotificationSender')
        if not $_[2]->{class_resolver};

    $_[0]->set_prefix($_[2]->{use_prefix}) if exists $_[2]->{use_prefix};
}

sub Renew {
    my ($self, $body, $header) = @_;
    die "Renew must be called as object method (\$self is <$self>)" if not blessed($self);
    return  $self->SUPER::call({
        operation => 'Renew',
        soap_action => 'http://docs.oasis-open.org/wsn/bw-2/Renew',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( WSNotification::Elements::Renew )],

        },
        header => {
           'use'           => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/ws/2004/08/addressing',
            encodingStyle   => '',
            parts           => [qw( WSNotification::Elements::Header )],
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub Unsubscribe {
    my ($self, $body, $header) = @_;
    die "Unsubscribe must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'Unsubscribe',
        soap_action => 'http://docs.oasis-open.org/wsn/bw-2/Unsubscribe',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( WSNotification::Elements::Unsubscribe )],

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

WSNotification::Interfaces::WSBaseNotificationSender::SubscriptionManagerPort - SOAP Interface for the WSBaseNotificationSender Web Service

=head1 SYNOPSIS

 use WSNotification::Interfaces::WSBaseNotificationSender::SubscriptionManagerPort;
 my $interface = WSNotification::Interfaces::WSBaseNotificationSender::SubscriptionManagerPort->new();

 my $response;
 $response = $interface->Renew();
 $response = $interface->Unsubscribe();



=head1 DESCRIPTION

SOAP Interface for the WSBaseNotificationSender web service
located at http://docs.oasis-open.org/wsn/bw-2.

=head1 SERVICE WSBaseNotificationSender



=head2 Port SubscriptionManagerPort



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



=head3 Renew



Returns a L<WSNotification::Elements::RenewResponse|WSNotification::Elements::RenewResponse> object.

 $response = $interface->Renew( {
    TerminationTime => $some_value, # AbsoluteOrRelativeTimeType
  },,
 );

=head3 Unsubscribe



Returns a L<WSNotification::Elements::UnsubscribeResponse|WSNotification::Elements::UnsubscribeResponse> object.

 $response = $interface->Unsubscribe( {
  },,
 );



=head1 AUTHOR

Generated by SOAP::WSDL on Fri Aug  8 16:49:21 2014

=cut
