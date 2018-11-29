package ONVIF::Device::Interfaces::Device::DevicePort;
use strict;
use warnings;
use Class::Std::Fast::Storable;
use Scalar::Util qw(blessed);
use base qw(SOAP::WSDL::Client::Base);

# only load if it hasn't been loaded before
require ONVIF::Device::Typemaps::Device
    if not ONVIF::Device::Typemaps::Device->can('get_class');

sub START {
    $_[0]->set_proxy('http://www.examples.com/Device/') if not $_[2]->{proxy};
    $_[0]->set_class_resolver('ONVIF::Device::Typemaps::Device')
        if not $_[2]->{class_resolver};

    $_[0]->set_prefix($_[2]->{use_prefix}) if exists $_[2]->{use_prefix};
}

sub GetServices {
    my ($self, $body, $header) = @_;
    die "GetServices must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetServices',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetServices',
        style => 'document',
        body => {
            use             => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetServices )],
        },
        header => {
 
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetServiceCapabilities {
    my ($self, $body, $header) = @_;
    die "GetServiceCapabilities must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetServiceCapabilities',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetServiceCapabilities',
        style => 'document',
        body => {
            use             => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetServiceCapabilities )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetDeviceInformation {
    my ($self, $body, $header) = @_;
    die "GetDeviceInformation must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetDeviceInformation',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetDeviceInformation',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetDeviceInformation )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetSystemDateAndTime {
    my ($self, $body, $header) = @_;
    die "SetSystemDateAndTime must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetSystemDateAndTime',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetSystemDateAndTime',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetSystemDateAndTime )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetSystemDateAndTime {
    my ($self, $body, $header) = @_;
    die "GetSystemDateAndTime must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetSystemDateAndTime',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetSystemDateAndTime',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetSystemDateAndTime )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetSystemFactoryDefault {
    my ($self, $body, $header) = @_;
    die "SetSystemFactoryDefault must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetSystemFactoryDefault',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetSystemFactoryDefault',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetSystemFactoryDefault )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub UpgradeSystemFirmware {
    my ($self, $body, $header) = @_;
    die "UpgradeSystemFirmware must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'UpgradeSystemFirmware',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/UpgradeSystemFirmware',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::UpgradeSystemFirmware )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SystemReboot {
    my ($self, $body, $header) = @_;
    die "SystemReboot must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SystemReboot',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SystemReboot',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SystemReboot )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RestoreSystem {
    my ($self, $body, $header) = @_;
    die "RestoreSystem must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RestoreSystem',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/RestoreSystem',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::RestoreSystem )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetSystemBackup {
    my ($self, $body, $header) = @_;
    die "GetSystemBackup must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetSystemBackup',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetSystemBackup',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetSystemBackup )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetSystemLog {
    my ($self, $body, $header) = @_;
    die "GetSystemLog must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetSystemLog',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetSystemLog',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetSystemLog )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetSystemSupportInformation {
    my ($self, $body, $header) = @_;
    die "GetSystemSupportInformation must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetSystemSupportInformation',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetSystemSupportInformation',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetSystemSupportInformation )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetScopes {
    my ($self, $body, $header) = @_;
    die "GetScopes must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetScopes',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetScopes',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetScopes )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetScopes {
    my ($self, $body, $header) = @_;
    die "SetScopes must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetScopes',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetScopes',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetScopes )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub AddScopes {
    my ($self, $body, $header) = @_;
    die "AddScopes must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'AddScopes',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/AddScopes',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::AddScopes )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RemoveScopes {
    my ($self, $body, $header) = @_;
    die "RemoveScopes must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RemoveScopes',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/RemoveScopes',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::RemoveScopes )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetDiscoveryMode {
    my ($self, $body, $header) = @_;
    die "GetDiscoveryMode must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetDiscoveryMode',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetDiscoveryMode',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetDiscoveryMode )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetDiscoveryMode {
    my ($self, $body, $header) = @_;
    die "SetDiscoveryMode must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetDiscoveryMode',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetDiscoveryMode',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetDiscoveryMode )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetRemoteDiscoveryMode {
    my ($self, $body, $header) = @_;
    die "GetRemoteDiscoveryMode must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetRemoteDiscoveryMode',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetRemoteDiscoveryMode',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetRemoteDiscoveryMode )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetRemoteDiscoveryMode {
    my ($self, $body, $header) = @_;
    die "SetRemoteDiscoveryMode must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetRemoteDiscoveryMode',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetRemoteDiscoveryMode',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetRemoteDiscoveryMode )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetDPAddresses {
    my ($self, $body, $header) = @_;
    die "GetDPAddresses must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetDPAddresses',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetDPAddresses',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetDPAddresses )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetEndpointReference {
    my ($self, $body, $header) = @_;
    die "GetEndpointReference must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetEndpointReference',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetEndpointReference',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetEndpointReference )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetRemoteUser {
    my ($self, $body, $header) = @_;
    die "GetRemoteUser must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetRemoteUser',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetRemoteUser',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetRemoteUser )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetRemoteUser {
    my ($self, $body, $header) = @_;
    die "SetRemoteUser must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetRemoteUser',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetRemoteUser',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetRemoteUser )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetUsers {
    my ($self, $body, $header) = @_;
    die "GetUsers must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetUsers',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetUsers',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetUsers )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub CreateUsers {
    my ($self, $body, $header) = @_;
    die "CreateUsers must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'CreateUsers',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/CreateUsers',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::CreateUsers )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub DeleteUsers {
    my ($self, $body, $header) = @_;
    die "DeleteUsers must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'DeleteUsers',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/DeleteUsers',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::DeleteUsers )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetUser {
    my ($self, $body, $header) = @_;
    die "SetUser must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetUser',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetUser',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetUser )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetWsdlUrl {
    my ($self, $body, $header) = @_;
    die "GetWsdlUrl must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetWsdlUrl',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetWsdlUrl',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetWsdlUrl )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetCapabilities {
    my ($self, $body, $header) = @_;
    die "GetCapabilities must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetCapabilities',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetCapabilities',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetCapabilities )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetDPAddresses {
    my ($self, $body, $header) = @_;
    die "SetDPAddresses must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetDPAddresses',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetDPAddresses',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetDPAddresses )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetHostname {
    my ($self, $body, $header) = @_;
    die "GetHostname must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetHostname',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetHostname',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetHostname )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetHostname {
    my ($self, $body, $header) = @_;
    die "SetHostname must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetHostname',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetHostname',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetHostname )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetHostnameFromDHCP {
    my ($self, $body, $header) = @_;
    die "SetHostnameFromDHCP must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetHostnameFromDHCP',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetHostnameFromDHCP',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetHostnameFromDHCP )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetDNS {
    my ($self, $body, $header) = @_;
    die "GetDNS must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetDNS',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetDNS',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetDNS )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetDNS {
    my ($self, $body, $header) = @_;
    die "SetDNS must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetDNS',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetDNS',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetDNS )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetNTP {
    my ($self, $body, $header) = @_;
    die "GetNTP must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetNTP',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetNTP',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetNTP )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetNTP {
    my ($self, $body, $header) = @_;
    die "SetNTP must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetNTP',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetNTP',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetNTP )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetDynamicDNS {
    my ($self, $body, $header) = @_;
    die "GetDynamicDNS must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetDynamicDNS',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetDynamicDNS',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetDynamicDNS )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetDynamicDNS {
    my ($self, $body, $header) = @_;
    die "SetDynamicDNS must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetDynamicDNS',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetDynamicDNS',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetDynamicDNS )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetNetworkInterfaces {
    my ($self, $body, $header) = @_;
    die "GetNetworkInterfaces must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetNetworkInterfaces',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetNetworkInterfaces',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetNetworkInterfaces )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetNetworkInterfaces {
    my ($self, $body, $header) = @_;
    die "SetNetworkInterfaces must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetNetworkInterfaces',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetNetworkInterfaces',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetNetworkInterfaces )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetNetworkProtocols {
    my ($self, $body, $header) = @_;
    die "GetNetworkProtocols must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetNetworkProtocols',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetNetworkProtocols',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetNetworkProtocols )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetNetworkProtocols {
    my ($self, $body, $header) = @_;
    die "SetNetworkProtocols must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetNetworkProtocols',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetNetworkProtocols',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetNetworkProtocols )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetNetworkDefaultGateway {
    my ($self, $body, $header) = @_;
    die "GetNetworkDefaultGateway must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetNetworkDefaultGateway',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetNetworkDefaultGateway',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetNetworkDefaultGateway )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetNetworkDefaultGateway {
    my ($self, $body, $header) = @_;
    die "SetNetworkDefaultGateway must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetNetworkDefaultGateway',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetNetworkDefaultGateway',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetNetworkDefaultGateway )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetZeroConfiguration {
    my ($self, $body, $header) = @_;
    die "GetZeroConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetZeroConfiguration',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetZeroConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetZeroConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetZeroConfiguration {
    my ($self, $body, $header) = @_;
    die "SetZeroConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetZeroConfiguration',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetZeroConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetZeroConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetIPAddressFilter {
    my ($self, $body, $header) = @_;
    die "GetIPAddressFilter must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetIPAddressFilter',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetIPAddressFilter',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetIPAddressFilter )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetIPAddressFilter {
    my ($self, $body, $header) = @_;
    die "SetIPAddressFilter must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetIPAddressFilter',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetIPAddressFilter',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetIPAddressFilter )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub AddIPAddressFilter {
    my ($self, $body, $header) = @_;
    die "AddIPAddressFilter must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'AddIPAddressFilter',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/AddIPAddressFilter',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::AddIPAddressFilter )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RemoveIPAddressFilter {
    my ($self, $body, $header) = @_;
    die "RemoveIPAddressFilter must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RemoveIPAddressFilter',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/RemoveIPAddressFilter',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::RemoveIPAddressFilter )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAccessPolicy {
    my ($self, $body, $header) = @_;
    die "GetAccessPolicy must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAccessPolicy',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetAccessPolicy',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetAccessPolicy )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetAccessPolicy {
    my ($self, $body, $header) = @_;
    die "SetAccessPolicy must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetAccessPolicy',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetAccessPolicy',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetAccessPolicy )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub CreateCertificate {
    my ($self, $body, $header) = @_;
    die "CreateCertificate must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'CreateCertificate',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/CreateCertificate',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::CreateCertificate )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetCertificates {
    my ($self, $body, $header) = @_;
    die "GetCertificates must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetCertificates',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetCertificates',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetCertificates )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetCertificatesStatus {
    my ($self, $body, $header) = @_;
    die "GetCertificatesStatus must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetCertificatesStatus',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetCertificatesStatus',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetCertificatesStatus )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetCertificatesStatus {
    my ($self, $body, $header) = @_;
    die "SetCertificatesStatus must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetCertificatesStatus',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetCertificatesStatus',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetCertificatesStatus )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub DeleteCertificates {
    my ($self, $body, $header) = @_;
    die "DeleteCertificates must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'DeleteCertificates',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/DeleteCertificates',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::DeleteCertificates )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetPkcs10Request {
    my ($self, $body, $header) = @_;
    die "GetPkcs10Request must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetPkcs10Request',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetPkcs10Request',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetPkcs10Request )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub LoadCertificates {
    my ($self, $body, $header) = @_;
    die "LoadCertificates must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'LoadCertificates',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/LoadCertificates',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::LoadCertificates )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetClientCertificateMode {
    my ($self, $body, $header) = @_;
    die "GetClientCertificateMode must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetClientCertificateMode',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetClientCertificateMode',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetClientCertificateMode )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetClientCertificateMode {
    my ($self, $body, $header) = @_;
    die "SetClientCertificateMode must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetClientCertificateMode',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetClientCertificateMode',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetClientCertificateMode )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetRelayOutputs {
    my ($self, $body, $header) = @_;
    die "GetRelayOutputs must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetRelayOutputs',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetRelayOutputs',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetRelayOutputs )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetRelayOutputSettings {
    my ($self, $body, $header) = @_;
    die "SetRelayOutputSettings must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetRelayOutputSettings',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetRelayOutputSettings',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetRelayOutputSettings )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetRelayOutputState {
    my ($self, $body, $header) = @_;
    die "SetRelayOutputState must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetRelayOutputState',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetRelayOutputState',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetRelayOutputState )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SendAuxiliaryCommand {
    my ($self, $body, $header) = @_;
    die "SendAuxiliaryCommand must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SendAuxiliaryCommand',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SendAuxiliaryCommand',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SendAuxiliaryCommand )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetCACertificates {
    my ($self, $body, $header) = @_;
    die "GetCACertificates must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetCACertificates',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetCACertificates',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetCACertificates )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub LoadCertificateWithPrivateKey {
    my ($self, $body, $header) = @_;
    die "LoadCertificateWithPrivateKey must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'LoadCertificateWithPrivateKey',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/LoadCertificateWithPrivateKey',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::LoadCertificateWithPrivateKey )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetCertificateInformation {
    my ($self, $body, $header) = @_;
    die "GetCertificateInformation must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetCertificateInformation',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetCertificateInformation',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetCertificateInformation )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub LoadCACertificates {
    my ($self, $body, $header) = @_;
    die "LoadCACertificates must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'LoadCACertificates',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/LoadCACertificates',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::LoadCACertificates )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub CreateDot1XConfiguration {
    my ($self, $body, $header) = @_;
    die "CreateDot1XConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'CreateDot1XConfiguration',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/CreateDot1XConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::CreateDot1XConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetDot1XConfiguration {
    my ($self, $body, $header) = @_;
    die "SetDot1XConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetDot1XConfiguration',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/SetDot1XConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::SetDot1XConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetDot1XConfiguration {
    my ($self, $body, $header) = @_;
    die "GetDot1XConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetDot1XConfiguration',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetDot1XConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetDot1XConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetDot1XConfigurations {
    my ($self, $body, $header) = @_;
    die "GetDot1XConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetDot1XConfigurations',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetDot1XConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetDot1XConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub DeleteDot1XConfiguration {
    my ($self, $body, $header) = @_;
    die "DeleteDot1XConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'DeleteDot1XConfiguration',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/DeleteDot1XConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::DeleteDot1XConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetDot11Capabilities {
    my ($self, $body, $header) = @_;
    die "GetDot11Capabilities must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetDot11Capabilities',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetDot11Capabilities',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetDot11Capabilities )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetDot11Status {
    my ($self, $body, $header) = @_;
    die "GetDot11Status must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetDot11Status',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetDot11Status',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetDot11Status )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub ScanAvailableDot11Networks {
    my ($self, $body, $header) = @_;
    die "ScanAvailableDot11Networks must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'ScanAvailableDot11Networks',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/ScanAvailableDot11Networks',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::ScanAvailableDot11Networks )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetSystemUris {
    my ($self, $body, $header) = @_;
    die "GetSystemUris must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetSystemUris',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/GetSystemUris',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::GetSystemUris )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub StartFirmwareUpgrade {
    my ($self, $body, $header) = @_;
    die "StartFirmwareUpgrade must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'StartFirmwareUpgrade',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/StartFirmwareUpgrade',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::StartFirmwareUpgrade )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub StartSystemRestore {
    my ($self, $body, $header) = @_;
    die "StartSystemRestore must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'StartSystemRestore',
        soap_action => 'http://www.onvif.org/ver10/device/wsdl/StartSystemRestore',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Device::Elements::StartSystemRestore )],
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

ONVIF::Device::Interfaces::Device::DevicePort - SOAP Interface for the Device Web Service

=head1 SYNOPSIS

 use ONVIF::Device::Interfaces::Device::DevicePort;
 my $interface = ONVIF::Device::Interfaces::Device::DevicePort->new();

 my $response;
 $response = $interface->GetServices();
 $response = $interface->GetServiceCapabilities();
 $response = $interface->GetDeviceInformation();
 $response = $interface->SetSystemDateAndTime();
 $response = $interface->GetSystemDateAndTime();
 $response = $interface->SetSystemFactoryDefault();
 $response = $interface->UpgradeSystemFirmware();
 $response = $interface->SystemReboot();
 $response = $interface->RestoreSystem();
 $response = $interface->GetSystemBackup();
 $response = $interface->GetSystemLog();
 $response = $interface->GetSystemSupportInformation();
 $response = $interface->GetScopes();
 $response = $interface->SetScopes();
 $response = $interface->AddScopes();
 $response = $interface->RemoveScopes();
 $response = $interface->GetDiscoveryMode();
 $response = $interface->SetDiscoveryMode();
 $response = $interface->GetRemoteDiscoveryMode();
 $response = $interface->SetRemoteDiscoveryMode();
 $response = $interface->GetDPAddresses();
 $response = $interface->GetEndpointReference();
 $response = $interface->GetRemoteUser();
 $response = $interface->SetRemoteUser();
 $response = $interface->GetUsers();
 $response = $interface->CreateUsers();
 $response = $interface->DeleteUsers();
 $response = $interface->SetUser();
 $response = $interface->GetWsdlUrl();
 $response = $interface->GetCapabilities();
 $response = $interface->SetDPAddresses();
 $response = $interface->GetHostname();
 $response = $interface->SetHostname();
 $response = $interface->SetHostnameFromDHCP();
 $response = $interface->GetDNS();
 $response = $interface->SetDNS();
 $response = $interface->GetNTP();
 $response = $interface->SetNTP();
 $response = $interface->GetDynamicDNS();
 $response = $interface->SetDynamicDNS();
 $response = $interface->GetNetworkInterfaces();
 $response = $interface->SetNetworkInterfaces();
 $response = $interface->GetNetworkProtocols();
 $response = $interface->SetNetworkProtocols();
 $response = $interface->GetNetworkDefaultGateway();
 $response = $interface->SetNetworkDefaultGateway();
 $response = $interface->GetZeroConfiguration();
 $response = $interface->SetZeroConfiguration();
 $response = $interface->GetIPAddressFilter();
 $response = $interface->SetIPAddressFilter();
 $response = $interface->AddIPAddressFilter();
 $response = $interface->RemoveIPAddressFilter();
 $response = $interface->GetAccessPolicy();
 $response = $interface->SetAccessPolicy();
 $response = $interface->CreateCertificate();
 $response = $interface->GetCertificates();
 $response = $interface->GetCertificatesStatus();
 $response = $interface->SetCertificatesStatus();
 $response = $interface->DeleteCertificates();
 $response = $interface->GetPkcs10Request();
 $response = $interface->LoadCertificates();
 $response = $interface->GetClientCertificateMode();
 $response = $interface->SetClientCertificateMode();
 $response = $interface->GetRelayOutputs();
 $response = $interface->SetRelayOutputSettings();
 $response = $interface->SetRelayOutputState();
 $response = $interface->SendAuxiliaryCommand();
 $response = $interface->GetCACertificates();
 $response = $interface->LoadCertificateWithPrivateKey();
 $response = $interface->GetCertificateInformation();
 $response = $interface->LoadCACertificates();
 $response = $interface->CreateDot1XConfiguration();
 $response = $interface->SetDot1XConfiguration();
 $response = $interface->GetDot1XConfiguration();
 $response = $interface->GetDot1XConfigurations();
 $response = $interface->DeleteDot1XConfiguration();
 $response = $interface->GetDot11Capabilities();
 $response = $interface->GetDot11Status();
 $response = $interface->ScanAvailableDot11Networks();
 $response = $interface->GetSystemUris();
 $response = $interface->StartFirmwareUpgrade();
 $response = $interface->StartSystemRestore();



=head1 DESCRIPTION

SOAP Interface for the Device web service
located at http://www.examples.com/Device/.

=head1 SERVICE Device



=head2 Port DevicePort



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



=head3 GetServices

Returns information about services on the device.

Returns a L<ONVIF::Device::Elements::GetServicesResponse|ONVIF::Device::Elements::GetServicesResponse> object.

 $response = $interface->GetServices( {
    IncludeCapability =>  $some_value, # boolean
  },,
 );

=head3 GetServiceCapabilities

Returns the capabilities of the device service. The result is returned in a typed answer.

Returns a L<ONVIF::Device::Elements::GetServiceCapabilitiesResponse|ONVIF::Device::Elements::GetServiceCapabilitiesResponse> object.

 $response = $interface->GetServiceCapabilities( {
  },,
 );

=head3 GetDeviceInformation

This operation gets basic device information from the device.

Returns a L<ONVIF::Device::Elements::GetDeviceInformationResponse|ONVIF::Device::Elements::GetDeviceInformationResponse> object.

 $response = $interface->GetDeviceInformation( {
  },,
 );

=head3 SetSystemDateAndTime

The DayLightSavings flag should be set to true to activate any DST settings of the TimeZone string. Clear the DayLightSavings flag if the DST portion of the TimeZone settings should be ignored. 

Returns a L<ONVIF::Device::Elements::SetSystemDateAndTimeResponse|ONVIF::Device::Elements::SetSystemDateAndTimeResponse> object.

 $response = $interface->SetSystemDateAndTime( {
    DateTimeType => $some_value, # SetDateTimeType
    DaylightSavings =>  $some_value, # boolean
    TimeZone =>  { # ONVIF::Device::Types::TimeZone
      TZ =>  $some_value, # token
    },
    UTCDateTime =>  { # ONVIF::Device::Types::DateTime
      Time =>  { # ONVIF::Device::Types::Time
        Hour =>  $some_value, # int
        Minute =>  $some_value, # int
        Second =>  $some_value, # int
      },
      Date =>  { # ONVIF::Device::Types::Date
        Year =>  $some_value, # int
        Month =>  $some_value, # int
        Day =>  $some_value, # int
      },
    },
  },,
 );

=head3 GetSystemDateAndTime

 A device shall provide the UTCDateTime information.

Returns a L<ONVIF::Device::Elements::GetSystemDateAndTimeResponse|ONVIF::Device::Elements::GetSystemDateAndTimeResponse> object.

 $response = $interface->GetSystemDateAndTime( {
  },,
 );

=head3 SetSystemFactoryDefault

This operation reloads the parameters on the device to their factory default values.

Returns a L<ONVIF::Device::Elements::SetSystemFactoryDefaultResponse|ONVIF::Device::Elements::SetSystemFactoryDefaultResponse> object.

 $response = $interface->SetSystemFactoryDefault( {
    FactoryDefault => $some_value, # FactoryDefaultType
  },,
 );

=head3 UpgradeSystemFirmware

This operation upgrades a device firmware version. After a successful upgrade the response message is sent before the device reboots. The device should support firmware upgrade through the UpgradeSystemFirmware command. The exact format of the firmware data is outside the scope of this standard.

Returns a L<ONVIF::Device::Elements::UpgradeSystemFirmwareResponse|ONVIF::Device::Elements::UpgradeSystemFirmwareResponse> object.

 $response = $interface->UpgradeSystemFirmware( {
    Firmware =>  { # ONVIF::Device::Types::AttachmentData
      Include =>  { # ONVIF::Device::Types::Include
      },
    },
  },,
 );

=head3 SystemReboot

This operation reboots the device.

Returns a L<ONVIF::Device::Elements::SystemRebootResponse|ONVIF::Device::Elements::SystemRebootResponse> object.

 $response = $interface->SystemReboot( {
  },,
 );

=head3 RestoreSystem

This operation restores the system backup configuration files(s) previously retrieved from a device. The device should support restore of backup configuration file(s) through the RestoreSystem command. The exact format of the backup configuration file(s) is outside the scope of this standard. If the command is supported, it shall accept backup files returned by the GetSystemBackup command.

Returns a L<ONVIF::Device::Elements::RestoreSystemResponse|ONVIF::Device::Elements::RestoreSystemResponse> object.

 $response = $interface->RestoreSystem( {
    BackupFiles =>  { # ONVIF::Device::Types::BackupFile
      Name =>  $some_value, # string
      Data =>  { # ONVIF::Device::Types::AttachmentData
        Include =>  { # ONVIF::Device::Types::Include
        },
      },
    },
  },,
 );

=head3 GetSystemBackup

This operation is retrieves system backup configuration file(s) from a device. The device should support return of back up configuration file(s) through the GetSystemBackup command. The backup is returned with reference to a name and mime-type together with binary data. The exact format of the backup configuration files is outside the scope of this standard.

Returns a L<ONVIF::Device::Elements::GetSystemBackupResponse|ONVIF::Device::Elements::GetSystemBackupResponse> object.

 $response = $interface->GetSystemBackup( {
  },,
 );

=head3 GetSystemLog

This operation gets a system log from the device. The exact format of the system logs is outside the scope of this standard.

Returns a L<ONVIF::Device::Elements::GetSystemLogResponse|ONVIF::Device::Elements::GetSystemLogResponse> object.

 $response = $interface->GetSystemLog( {
    LogType => $some_value, # SystemLogType
  },,
 );

=head3 GetSystemSupportInformation

This operation gets arbitrary device diagnostics information from the device.

Returns a L<ONVIF::Device::Elements::GetSystemSupportInformationResponse|ONVIF::Device::Elements::GetSystemSupportInformationResponse> object.

 $response = $interface->GetSystemSupportInformation( {
  },,
 );

=head3 GetScopes

Configurable Fixed scope parameters are permanent device characteristics and cannot be removed through the device management interface. The scope type is indicated in the scope list returned in the get scope parameters response. A device shall support retrieval of discovery scope parameters through the GetScopes command. As some scope parameters are mandatory, the device shall return a non-empty scope list in the response.

Returns a L<ONVIF::Device::Elements::GetScopesResponse|ONVIF::Device::Elements::GetScopesResponse> object.

 $response = $interface->GetScopes( {
  },,
 );

=head3 SetScopes

This operation sets the scope parameters of a device. The scope parameters are used in the device discovery to match a probe message. This operation replaces all existing configurable scope parameters (not fixed parameters). If this shall be avoided, one should use the scope add command instead. The device shall support configuration of discovery scope parameters through the SetScopes command.

Returns a L<ONVIF::Device::Elements::SetScopesResponse|ONVIF::Device::Elements::SetScopesResponse> object.

 $response = $interface->SetScopes( {
    Scopes =>  $some_value, # anyURI
  },,
 );

=head3 AddScopes

This operation adds new configurable scope parameters to a device. The scope parameters are used in the device discovery to match a probe message. The device shall support addition of discovery scope parameters through the AddScopes command.

Returns a L<ONVIF::Device::Elements::AddScopesResponse|ONVIF::Device::Elements::AddScopesResponse> object.

 $response = $interface->AddScopes( {
    ScopeItem =>  $some_value, # anyURI
  },,
 );

=head3 RemoveScopes

This operation deletes scope-configurable scope parameters from a device. The scope parameters are used in the device discovery to match a probe message, see Section 7. The device shall support deletion of discovery scope parameters through the RemoveScopes command. Table

Returns a L<ONVIF::Device::Elements::RemoveScopesResponse|ONVIF::Device::Elements::RemoveScopesResponse> object.

 $response = $interface->RemoveScopes( {
    ScopeItem =>  $some_value, # anyURI
  },,
 );

=head3 GetDiscoveryMode

This operation gets the discovery mode of a device. See Section 7.2 for the definition of the different device discovery modes. The device shall support retrieval of the discovery mode setting through the GetDiscoveryMode command.

Returns a L<ONVIF::Device::Elements::GetDiscoveryModeResponse|ONVIF::Device::Elements::GetDiscoveryModeResponse> object.

 $response = $interface->GetDiscoveryMode( {
  },,
 );

=head3 SetDiscoveryMode

This operation sets the discovery mode operation of a device. See Section 7.2 for the definition of the different device discovery modes. The device shall support configuration of the discovery mode setting through the SetDiscoveryMode command.

Returns a L<ONVIF::Device::Elements::SetDiscoveryModeResponse|ONVIF::Device::Elements::SetDiscoveryModeResponse> object.

 $response = $interface->SetDiscoveryMode( {
    DiscoveryMode => $some_value, # DiscoveryMode
  },,
 );

=head3 GetRemoteDiscoveryMode

This operation gets the remote discovery mode of a device. See Section 7.4 for the definition of remote discovery extensions. A device that supports remote discovery shall support retrieval of the remote discovery mode setting through the GetRemoteDiscoveryMode command.

Returns a L<ONVIF::Device::Elements::GetRemoteDiscoveryModeResponse|ONVIF::Device::Elements::GetRemoteDiscoveryModeResponse> object.

 $response = $interface->GetRemoteDiscoveryMode( {
  },,
 );

=head3 SetRemoteDiscoveryMode

This operation sets the remote discovery mode of operation of a device. See Section 7.4 for the definition of remote discovery remote extensions. A device that supports remote discovery shall support configuration of the discovery mode setting through the SetRemoteDiscoveryMode command.

Returns a L<ONVIF::Device::Elements::SetRemoteDiscoveryModeResponse|ONVIF::Device::Elements::SetRemoteDiscoveryModeResponse> object.

 $response = $interface->SetRemoteDiscoveryMode( {
    RemoteDiscoveryMode => $some_value, # DiscoveryMode
  },,
 );

=head3 GetDPAddresses

This operation gets the remote DP address or addresses from a device. If the device supports remote discovery, as specified in Section 7.4, the device shall support retrieval of the remote DP address(es) through the GetDPAddresses command.

Returns a L<ONVIF::Device::Elements::GetDPAddressesResponse|ONVIF::Device::Elements::GetDPAddressesResponse> object.

 $response = $interface->GetDPAddresses( {
  },,
 );

=head3 GetEndpointReference

A client can ask for the device service endpoint reference address property that can be used to derive the password equivalent for remote user operation. The device shall support the GetEndpointReference command returning the address property of the device service endpoint reference.

Returns a L<ONVIF::Device::Elements::GetEndpointReferenceResponse|ONVIF::Device::Elements::GetEndpointReferenceResponse> object.

 $response = $interface->GetEndpointReference( {
  },,
 );

=head3 GetRemoteUser

 The algorithm to use for deriving the password is described in section 5.12.2.1 of the core specification.

Returns a L<ONVIF::Device::Elements::GetRemoteUserResponse|ONVIF::Device::Elements::GetRemoteUserResponse> object.

 $response = $interface->GetRemoteUser( {
  },,
 );

=head3 SetRemoteUser

 To remove the remote user SetRemoteUser should be called without the RemoteUser parameter.

Returns a L<ONVIF::Device::Elements::SetRemoteUserResponse|ONVIF::Device::Elements::SetRemoteUserResponse> object.

 $response = $interface->SetRemoteUser( {
    RemoteUser =>  { # ONVIF::Device::Types::RemoteUser
      Username =>  $some_value, # string
      Password =>  $some_value, # string
      UseDerivedPassword =>  $some_value, # boolean
    },
  },,
 );

=head3 GetUsers

This operation lists the registered users and corresponding credentials on a device. The device shall support retrieval of registered device users and their credentials for the user token through the GetUsers command.

Returns a L<ONVIF::Device::Elements::GetUsersResponse|ONVIF::Device::Elements::GetUsersResponse> object.

 $response = $interface->GetUsers( {
  },,
 );

=head3 CreateUsers

 ONVIF compliant devices are recommended to support password length of at least 28 bytes, as clients may follow the password derivation mechanism which results in 'password equivalent' of length 28 bytes, as described in section 3.1.2 of the ONVIF security white paper.

Returns a L<ONVIF::Device::Elements::CreateUsersResponse|ONVIF::Device::Elements::CreateUsersResponse> object.

 $response = $interface->CreateUsers( {
    User =>  { # ONVIF::Device::Types::User
      Username =>  $some_value, # string
      Password =>  $some_value, # string
      UserLevel => $some_value, # UserLevel
      Extension =>  { # ONVIF::Device::Types::UserExtension
      },
    },
  },,
 );

=head3 DeleteUsers

This operation deletes users on a device. The device shall support deletion of device users and their credentials through the DeleteUsers command. A device may have one or more fixed users that cannot be deleted to ensure access to the unit. Either all users are deleted successfully or a fault message shall be returned and no users be deleted.

Returns a L<ONVIF::Device::Elements::DeleteUsersResponse|ONVIF::Device::Elements::DeleteUsersResponse> object.

 $response = $interface->DeleteUsers( {
    Username =>  $some_value, # string
  },,
 );

=head3 SetUser

This operation updates the settings for one or several users on a device for authentication purposes. The device shall support update of device users and their credentials through the SetUser command. Either all change requests are processed successfully or a fault message shall be returned and no change requests be processed.

Returns a L<ONVIF::Device::Elements::SetUserResponse|ONVIF::Device::Elements::SetUserResponse> object.

 $response = $interface->SetUser( {
    User =>  { # ONVIF::Device::Types::User
      Username =>  $some_value, # string
      Password =>  $some_value, # string
      UserLevel => $some_value, # UserLevel
      Extension =>  { # ONVIF::Device::Types::UserExtension
      },
    },
  },,
 );

=head3 GetWsdlUrl

It is possible for an endpoint to request a URL that can be used to retrieve the complete schema and WSDL definitions of a device. The command gives in return a URL entry point where all the necessary product specific WSDL and schema definitions can be retrieved. The device shall provide a URL for WSDL and schema download through the GetWsdlUrl command.

Returns a L<ONVIF::Device::Elements::GetWsdlUrlResponse|ONVIF::Device::Elements::GetWsdlUrlResponse> object.

 $response = $interface->GetWsdlUrl( {
  },,
 );

=head3 GetCapabilities

Any endpoint can ask for the capabilities of a device using the capability exchange request response operation. The device shall indicate all its ONVIF compliant capabilities through the GetCapabilities command. The capability list includes references to the addresses (XAddr) of the service implementing the interface operations in the category. Apart from the addresses, the capabilities only reflect optional functions.

Returns a L<ONVIF::Device::Elements::GetCapabilitiesResponse|ONVIF::Device::Elements::GetCapabilitiesResponse> object.

 $response = $interface->GetCapabilities( {
    Category => $some_value, # CapabilityCategory
  },,
 );

=head3 SetDPAddresses

This operation sets the remote DP address or addresses on a device. If the device supports remote discovery, as specified in Section 7.4, the device shall support configuration of the remote DP address(es) through the SetDPAddresses command.

Returns a L<ONVIF::Device::Elements::SetDPAddressesResponse|ONVIF::Device::Elements::SetDPAddressesResponse> object.

 $response = $interface->SetDPAddresses( {
    DPAddress =>  { # ONVIF::Device::Types::NetworkHost
      Type => $some_value, # NetworkHostType
      IPv4Address => $some_value, # IPv4Address
      IPv6Address => $some_value, # IPv6Address
      DNSname => $some_value, # DNSName
      Extension =>  { # ONVIF::Device::Types::NetworkHostExtension
      },
    },
  },,
 );

=head3 GetHostname

This operation is used by an endpoint to get the hostname from a device. The device shall return its hostname configurations through the GetHostname command.

Returns a L<ONVIF::Device::Elements::GetHostnameResponse|ONVIF::Device::Elements::GetHostnameResponse> object.

 $response = $interface->GetHostname( {
  },,
 );

=head3 SetHostname

A device shall accept string formatted according to RFC 1123 section 2.1 or alternatively to RFC 952, other string shall be considered as invalid strings.

Returns a L<ONVIF::Device::Elements::SetHostnameResponse|ONVIF::Device::Elements::SetHostnameResponse> object.

 $response = $interface->SetHostname( {
    Name =>  $some_value, # token
  },,
 );

=head3 SetHostnameFromDHCP

This operation controls whether the hostname is set manually or retrieved via DHCP.

Returns a L<ONVIF::Device::Elements::SetHostnameFromDHCPResponse|ONVIF::Device::Elements::SetHostnameFromDHCPResponse> object.

 $response = $interface->SetHostnameFromDHCP( {
    FromDHCP =>  $some_value, # boolean
  },,
 );

=head3 GetDNS

This operation gets the DNS settings from a device. The device shall return its DNS configurations through the GetDNS command.

Returns a L<ONVIF::Device::Elements::GetDNSResponse|ONVIF::Device::Elements::GetDNSResponse> object.

 $response = $interface->GetDNS( {
  },,
 );

=head3 SetDNS

This operation sets the DNS settings on a device. It shall be possible to set the device DNS configurations through the SetDNS command.

Returns a L<ONVIF::Device::Elements::SetDNSResponse|ONVIF::Device::Elements::SetDNSResponse> object.

 $response = $interface->SetDNS( {
    FromDHCP =>  $some_value, # boolean
    SearchDomain =>  $some_value, # token
    DNSManual =>  { # ONVIF::Device::Types::IPAddress
      Type => $some_value, # IPType
      IPv4Address => $some_value, # IPv4Address
      IPv6Address => $some_value, # IPv6Address
    },
  },,
 );

=head3 GetNTP

This operation gets the NTP settings from a device. If the device supports NTP, it shall be possible to get the NTP server settings through the GetNTP command.

Returns a L<ONVIF::Device::Elements::GetNTPResponse|ONVIF::Device::Elements::GetNTPResponse> object.

 $response = $interface->GetNTP( {
  },,
 );

=head3 SetNTP

Changes to the NTP server list will not affect the clock mode DateTimeType. Use SetSystemDateAndTime to activate NTP operation. 

Returns a L<ONVIF::Device::Elements::SetNTPResponse|ONVIF::Device::Elements::SetNTPResponse> object.

 $response = $interface->SetNTP( {
    FromDHCP =>  $some_value, # boolean
    NTPManual =>  { # ONVIF::Device::Types::NetworkHost
      Type => $some_value, # NetworkHostType
      IPv4Address => $some_value, # IPv4Address
      IPv6Address => $some_value, # IPv6Address
      DNSname => $some_value, # DNSName
      Extension =>  { # ONVIF::Device::Types::NetworkHostExtension
      },
    },
  },,
 );

=head3 GetDynamicDNS

This operation gets the dynamic DNS settings from a device. If the device supports dynamic DNS as specified in [RFC 2136] and [RFC 4702], it shall be possible to get the type, name and TTL through the GetDynamicDNS command.

Returns a L<ONVIF::Device::Elements::GetDynamicDNSResponse|ONVIF::Device::Elements::GetDynamicDNSResponse> object.

 $response = $interface->GetDynamicDNS( {
  },,
 );

=head3 SetDynamicDNS

This operation sets the dynamic DNS settings on a device. If the device supports dynamic DNS as specified in [RFC 2136] and [RFC 4702], it shall be possible to set the type, name and TTL through the SetDynamicDNS command.

Returns a L<ONVIF::Device::Elements::SetDynamicDNSResponse|ONVIF::Device::Elements::SetDynamicDNSResponse> object.

 $response = $interface->SetDynamicDNS( {
    Type => $some_value, # DynamicDNSType
    Name => $some_value, # DNSName
    TTL =>  $some_value, # duration
  },,
 );

=head3 GetNetworkInterfaces

This operation gets the network interface configuration from a device. The device shall support return of network interface configuration settings as defined by the NetworkInterface type through the GetNetworkInterfaces command.

Returns a L<ONVIF::Device::Elements::GetNetworkInterfacesResponse|ONVIF::Device::Elements::GetNetworkInterfacesResponse> object.

 $response = $interface->GetNetworkInterfaces( {
  },,
 );

=head3 SetNetworkInterfaces

 For interoperability with a client unaware of the IEEE 802.11 extension a device shall retain its IEEE 802.11 configuration if the IEEE 802.11 configuration element isn’t present in the request.

Returns a L<ONVIF::Device::Elements::SetNetworkInterfacesResponse|ONVIF::Device::Elements::SetNetworkInterfacesResponse> object.

 $response = $interface->SetNetworkInterfaces( {
    InterfaceToken => $some_value, # ReferenceToken
    NetworkInterface =>  { # ONVIF::Device::Types::NetworkInterfaceSetConfiguration
      Enabled =>  $some_value, # boolean
      Link =>  { # ONVIF::Device::Types::NetworkInterfaceConnectionSetting
        AutoNegotiation =>  $some_value, # boolean
        Speed =>  $some_value, # int
        Duplex => $some_value, # Duplex
      },
      MTU =>  $some_value, # int
      IPv4 =>  { # ONVIF::Device::Types::IPv4NetworkInterfaceSetConfiguration
        Enabled =>  $some_value, # boolean
        Manual =>  { # ONVIF::Device::Types::PrefixedIPv4Address
          Address => $some_value, # IPv4Address
          PrefixLength =>  $some_value, # int
        },
        DHCP =>  $some_value, # boolean
      },
      IPv6 =>  { # ONVIF::Device::Types::IPv6NetworkInterfaceSetConfiguration
        Enabled =>  $some_value, # boolean
        AcceptRouterAdvert =>  $some_value, # boolean
        Manual =>  { # ONVIF::Device::Types::PrefixedIPv6Address
          Address => $some_value, # IPv6Address
          PrefixLength =>  $some_value, # int
        },
        DHCP => $some_value, # IPv6DHCPConfiguration
      },
      Extension =>  { # ONVIF::Device::Types::NetworkInterfaceSetConfigurationExtension
        Dot3 =>  { # ONVIF::Device::Types::Dot3Configuration
        },
        Dot11 =>  { # ONVIF::Device::Types::Dot11Configuration
          SSID => $some_value, # Dot11SSIDType
          Mode => $some_value, # Dot11StationMode
          Alias => $some_value, # Name
          Priority => $some_value, # NetworkInterfaceConfigPriority
          Security =>  { # ONVIF::Device::Types::Dot11SecurityConfiguration
            Mode => $some_value, # Dot11SecurityMode
            Algorithm => $some_value, # Dot11Cipher
            PSK =>  { # ONVIF::Device::Types::Dot11PSKSet
              Key => $some_value, # Dot11PSK
              Passphrase => $some_value, # Dot11PSKPassphrase
              Extension =>  { # ONVIF::Device::Types::Dot11PSKSetExtension
              },
            },
            Dot1X => $some_value, # ReferenceToken
            Extension =>  { # ONVIF::Device::Types::Dot11SecurityConfigurationExtension
            },
          },
        },
        Extension =>  { # ONVIF::Device::Types::NetworkInterfaceSetConfigurationExtension2
        },
      },
    },
  },,
 );

=head3 GetNetworkProtocols

This operation gets defined network protocols from a device. The device shall support the GetNetworkProtocols command returning configured network protocols.

Returns a L<ONVIF::Device::Elements::GetNetworkProtocolsResponse|ONVIF::Device::Elements::GetNetworkProtocolsResponse> object.

 $response = $interface->GetNetworkProtocols( {
  },,
 );

=head3 SetNetworkProtocols

This operation configures defined network protocols on a device. The device shall support configuration of defined network protocols through the SetNetworkProtocols command.

Returns a L<ONVIF::Device::Elements::SetNetworkProtocolsResponse|ONVIF::Device::Elements::SetNetworkProtocolsResponse> object.

 $response = $interface->SetNetworkProtocols( {
    NetworkProtocols =>  { # ONVIF::Device::Types::NetworkProtocol
      Name => $some_value, # NetworkProtocolType
      Enabled =>  $some_value, # boolean
      Port =>  $some_value, # int
      Extension =>  { # ONVIF::Device::Types::NetworkProtocolExtension
      },
    },
  },,
 );

=head3 GetNetworkDefaultGateway

This operation gets the default gateway settings from a device. The device shall support the GetNetworkDefaultGateway command returning configured default gateway address(es).

Returns a L<ONVIF::Device::Elements::GetNetworkDefaultGatewayResponse|ONVIF::Device::Elements::GetNetworkDefaultGatewayResponse> object.

 $response = $interface->GetNetworkDefaultGateway( {
  },,
 );

=head3 SetNetworkDefaultGateway

This operation sets the default gateway settings on a device. The device shall support configuration of default gateway through the SetNetworkDefaultGateway command.

Returns a L<ONVIF::Device::Elements::SetNetworkDefaultGatewayResponse|ONVIF::Device::Elements::SetNetworkDefaultGatewayResponse> object.

 $response = $interface->SetNetworkDefaultGateway( {
    IPv4Address => $some_value, # IPv4Address
    IPv6Address => $some_value, # IPv6Address
  },,
 );

=head3 GetZeroConfiguration

 Devices supporting zero configuration on more than one interface shall use the extension to list the additional interface settings.

Returns a L<ONVIF::Device::Elements::GetZeroConfigurationResponse|ONVIF::Device::Elements::GetZeroConfigurationResponse> object.

 $response = $interface->GetZeroConfiguration( {
  },,
 );

=head3 SetZeroConfiguration

This operation sets the zero-configuration. Use GetCapalities to get if zero-zero-configuration is supported or not.

Returns a L<ONVIF::Device::Elements::SetZeroConfigurationResponse|ONVIF::Device::Elements::SetZeroConfigurationResponse> object.

 $response = $interface->SetZeroConfiguration( {
    InterfaceToken => $some_value, # ReferenceToken
    Enabled =>  $some_value, # boolean
  },,
 );

=head3 GetIPAddressFilter

This operation gets the IP address filter settings from a device. If the device supports device access control based on IP filtering rules (denied or accepted ranges of IP addresses), the device shall support the GetIPAddressFilter command.

Returns a L<ONVIF::Device::Elements::GetIPAddressFilterResponse|ONVIF::Device::Elements::GetIPAddressFilterResponse> object.

 $response = $interface->GetIPAddressFilter( {
  },,
 );

=head3 SetIPAddressFilter

This operation sets the IP address filter settings on a device. If the device supports device access control based on IP filtering rules (denied or accepted ranges of IP addresses), the device shall support configuration of IP filtering rules through the SetIPAddressFilter command.

Returns a L<ONVIF::Device::Elements::SetIPAddressFilterResponse|ONVIF::Device::Elements::SetIPAddressFilterResponse> object.

 $response = $interface->SetIPAddressFilter( {
    IPAddressFilter =>  { # ONVIF::Device::Types::IPAddressFilter
      Type => $some_value, # IPAddressFilterType
      IPv4Address =>  { # ONVIF::Device::Types::PrefixedIPv4Address
        Address => $some_value, # IPv4Address
        PrefixLength =>  $some_value, # int
      },
      IPv6Address =>  { # ONVIF::Device::Types::PrefixedIPv6Address
        Address => $some_value, # IPv6Address
        PrefixLength =>  $some_value, # int
      },
      Extension =>  { # ONVIF::Device::Types::IPAddressFilterExtension
      },
    },
  },,
 );

=head3 AddIPAddressFilter

This operation adds an IP filter address to a device. If the device supports device access control based on IP filtering rules (denied or accepted ranges of IP addresses), the device shall support adding of IP filtering addresses through the AddIPAddressFilter command.

Returns a L<ONVIF::Device::Elements::AddIPAddressFilterResponse|ONVIF::Device::Elements::AddIPAddressFilterResponse> object.

 $response = $interface->AddIPAddressFilter( {
    IPAddressFilter =>  { # ONVIF::Device::Types::IPAddressFilter
      Type => $some_value, # IPAddressFilterType
      IPv4Address =>  { # ONVIF::Device::Types::PrefixedIPv4Address
        Address => $some_value, # IPv4Address
        PrefixLength =>  $some_value, # int
      },
      IPv6Address =>  { # ONVIF::Device::Types::PrefixedIPv6Address
        Address => $some_value, # IPv6Address
        PrefixLength =>  $some_value, # int
      },
      Extension =>  { # ONVIF::Device::Types::IPAddressFilterExtension
      },
    },
  },,
 );

=head3 RemoveIPAddressFilter

This operation deletes an IP filter address from a device. If the device supports device access control based on IP filtering rules (denied or accepted ranges of IP addresses), the device shall support deletion of IP filtering addresses through the RemoveIPAddressFilter command.

Returns a L<ONVIF::Device::Elements::RemoveIPAddressFilterResponse|ONVIF::Device::Elements::RemoveIPAddressFilterResponse> object.

 $response = $interface->RemoveIPAddressFilter( {
    IPAddressFilter =>  { # ONVIF::Device::Types::IPAddressFilter
      Type => $some_value, # IPAddressFilterType
      IPv4Address =>  { # ONVIF::Device::Types::PrefixedIPv4Address
        Address => $some_value, # IPv4Address
        PrefixLength =>  $some_value, # int
      },
      IPv6Address =>  { # ONVIF::Device::Types::PrefixedIPv6Address
        Address => $some_value, # IPv6Address
        PrefixLength =>  $some_value, # int
      },
      Extension =>  { # ONVIF::Device::Types::IPAddressFilterExtension
      },
    },
  },,
 );

=head3 GetAccessPolicy

Access to different services and sub-sets of services should be subject to access control. The WS-Security framework gives the prerequisite for end-point authentication. Authorization decisions can then be taken using an access security policy. This standard does not mandate any particular policy description format or security policy but this is up to the device manufacturer or system provider to choose policy and policy description format of choice. However, an access policy (in arbitrary format) can be requested using this command. If the device supports access policy settings based on WS-Security authentication, then the device shall support this command.

Returns a L<ONVIF::Device::Elements::GetAccessPolicyResponse|ONVIF::Device::Elements::GetAccessPolicyResponse> object.

 $response = $interface->GetAccessPolicy( {
  },,
 );

=head3 SetAccessPolicy

This command sets the device access security policy (for more details on the access security policy see the Get command). If the device supports access policy settings based on WS-Security authentication, then the device shall support this command.

Returns a L<ONVIF::Device::Elements::SetAccessPolicyResponse|ONVIF::Device::Elements::SetAccessPolicyResponse> object.

 $response = $interface->SetAccessPolicy( {
    PolicyFile =>  { # ONVIF::Device::Types::BinaryData
      Data =>  $some_value, # base64Binary
    },
  },,
 );

=head3 CreateCertificate

 If a device supports onboard key pair generation, the device that supports TLS shall support this certificate creation command. And also, if a device supports onboard key pair generation, the device that support IEEE 802.1X shall support this command for the purpose of key pair generation. Certificates and key pairs are identified using certificate IDs. These IDs are either chosen by the certificate generation requester or by the device (in case that no ID value is given).

Returns a L<ONVIF::Device::Elements::CreateCertificateResponse|ONVIF::Device::Elements::CreateCertificateResponse> object.

 $response = $interface->CreateCertificate( {
    CertificateID =>  $some_value, # token
    Subject =>  $some_value, # string
    ValidNotBefore =>  $some_value, # dateTime
    ValidNotAfter =>  $some_value, # dateTime
  },,
 );

=head3 GetCertificates

This operation gets all device server certificates (including self-signed) for the purpose of TLS authentication and all device client certificates for the purpose of IEEE 802.1X authentication. This command lists only the TLS server certificates and IEEE 802.1X client certificates for the device (neither trusted CA certificates nor trusted root certificates). The certificates are returned as binary data. A device that supports TLS shall support this command and the certificates shall be encoded using ASN.1 [X.681], [X.682], [X.683] DER [X.690] encoding rules.

Returns a L<ONVIF::Device::Elements::GetCertificatesResponse|ONVIF::Device::Elements::GetCertificatesResponse> object.

 $response = $interface->GetCertificates( {
  },,
 );

=head3 GetCertificatesStatus

This operation is specific to TLS functionality. This operation gets the status (enabled/disabled) of the device TLS server certificates. A device that supports TLS shall support this command.

Returns a L<ONVIF::Device::Elements::GetCertificatesStatusResponse|ONVIF::Device::Elements::GetCertificatesStatusResponse> object.

 $response = $interface->GetCertificatesStatus( {
  },,
 );

=head3 SetCertificatesStatus

This operation is specific to TLS functionality. This operation sets the status (enable/disable) of the device TLS server certificates. A device that supports TLS shall support this command. Typically only one device server certificate is allowed to be enabled at a time.

Returns a L<ONVIF::Device::Elements::SetCertificatesStatusResponse|ONVIF::Device::Elements::SetCertificatesStatusResponse> object.

 $response = $interface->SetCertificatesStatus( {
    CertificateStatus =>  { # ONVIF::Device::Types::CertificateStatus
      CertificateID =>  $some_value, # token
      Status =>  $some_value, # boolean
    },
  },,
 );

=head3 DeleteCertificates

This operation deletes a certificate or multiple certificates. The device MAY also delete a private/public key pair which is coupled with the certificate to be deleted. The device that support either TLS or IEEE 802.1X shall support the deletion of a certificate or multiple certificates through this command. Either all certificates are deleted successfully or a fault message shall be returned without deleting any certificate.

Returns a L<ONVIF::Device::Elements::DeleteCertificatesResponse|ONVIF::Device::Elements::DeleteCertificatesResponse> object.

 $response = $interface->DeleteCertificates( {
    CertificateID =>  $some_value, # token
  },,
 );

=head3 GetPkcs10Request

 A device that support onboard key pair generation that supports either TLS or IEEE 802.1X using client certificate shall support this command.

Returns a L<ONVIF::Device::Elements::GetPkcs10RequestResponse|ONVIF::Device::Elements::GetPkcs10RequestResponse> object.

 $response = $interface->GetPkcs10Request( {
    CertificateID =>  $some_value, # token
    Subject =>  $some_value, # string
    Attributes =>  { # ONVIF::Device::Types::BinaryData
      Data =>  $some_value, # base64Binary
    },
  },,
 );

=head3 LoadCertificates

 This command is applicable to any device type, although the parameter name is called for historical reasons NVTCertificate.

Returns a L<ONVIF::Device::Elements::LoadCertificatesResponse|ONVIF::Device::Elements::LoadCertificatesResponse> object.

 $response = $interface->LoadCertificates( {
    NVTCertificate =>  { # ONVIF::Device::Types::Certificate
      CertificateID =>  $some_value, # token
      Certificate =>  { # ONVIF::Device::Types::BinaryData
        Data =>  $some_value, # base64Binary
      },
    },
  },,
 );

=head3 GetClientCertificateMode

This operation is specific to TLS functionality. This operation gets the status (enabled/disabled) of the device TLS client authentication. A device that supports TLS shall support this command.

Returns a L<ONVIF::Device::Elements::GetClientCertificateModeResponse|ONVIF::Device::Elements::GetClientCertificateModeResponse> object.

 $response = $interface->GetClientCertificateMode( {
  },,
 );

=head3 SetClientCertificateMode

This operation is specific to TLS functionality. This operation sets the status (enabled/disabled) of the device TLS client authentication. A device that supports TLS shall support this command.

Returns a L<ONVIF::Device::Elements::SetClientCertificateModeResponse|ONVIF::Device::Elements::SetClientCertificateModeResponse> object.

 $response = $interface->SetClientCertificateMode( {
    Enabled =>  $some_value, # boolean
  },,
 );

=head3 GetRelayOutputs

 This method has been deprecated with version 2.0. Refer to the DeviceIO service.

Returns a L<ONVIF::Device::Elements::GetRelayOutputsResponse|ONVIF::Device::Elements::GetRelayOutputsResponse> object.

 $response = $interface->GetRelayOutputs( {
  },,
 );

=head3 SetRelayOutputSettings

This method has been deprecated with version 2.0. Refer to the DeviceIO service.

Returns a L<ONVIF::Device::Elements::SetRelayOutputSettingsResponse|ONVIF::Device::Elements::SetRelayOutputSettingsResponse> object.

 $response = $interface->SetRelayOutputSettings( {
    RelayOutputToken => $some_value, # ReferenceToken
    Properties =>  { # ONVIF::Device::Types::RelayOutputSettings
      Mode => $some_value, # RelayMode
      DelayTime =>  $some_value, # duration
      IdleState => $some_value, # RelayIdleState
    },
  },,
 );

=head3 SetRelayOutputState

This method has been deprecated with version 2.0. Refer to the DeviceIO service.

Returns a L<ONVIF::Device::Elements::SetRelayOutputStateResponse|ONVIF::Device::Elements::SetRelayOutputStateResponse> object.

 $response = $interface->SetRelayOutputState( {
    RelayOutputToken => $some_value, # ReferenceToken
    LogicalState => $some_value, # RelayLogicalState
  },,
 );

=head3 SendAuxiliaryCommand

tt:IRLamp|Auto – Request to configure an IR illuminator attached to the unit so that it automatically turns ON and OFF. A device that indicates auxiliary service capability shall support this command.

Returns a L<ONVIF::Device::Elements::SendAuxiliaryCommandResponse|ONVIF::Device::Elements::SendAuxiliaryCommandResponse> object.

 $response = $interface->SendAuxiliaryCommand( {
    AuxiliaryCommand => $some_value, # AuxiliaryData
  },,
 );

=head3 GetCACertificates

CA certificates will be loaded into a device and be used for the sake of following two cases. The one is for the purpose of TLS client authentication in TLS server function. The other one is for the purpose of Authentication Server authentication in IEEE 802.1X function. This operation gets all CA certificates loaded into a device. A device that supports either TLS client authentication or IEEE 802.1X shall support this command and the returned certificates shall be encoded using ASN.1 [X.681], [X.682], [X.683] DER [X.690] encoding rules.

Returns a L<ONVIF::Device::Elements::GetCACertificatesResponse|ONVIF::Device::Elements::GetCACertificatesResponse> object.

 $response = $interface->GetCACertificates( {
  },,
 );

=head3 LoadCertificateWithPrivateKey

 A device that does not support onboard key pair generation and support either TLS or IEEE 802.1X using client certificate shall support this command. A device that support onboard key pair generation MAY support this command. The security policy of a device that supports this operation should make sure that the private key is sufficiently protected.

Returns a L<ONVIF::Device::Elements::LoadCertificateWithPrivateKeyResponse|ONVIF::Device::Elements::LoadCertificateWithPrivateKeyResponse> object.

 $response = $interface->LoadCertificateWithPrivateKey( {
    CertificateWithPrivateKey =>  { # ONVIF::Device::Types::CertificateWithPrivateKey
      CertificateID =>  $some_value, # token
      Certificate =>  { # ONVIF::Device::Types::BinaryData
        Data =>  $some_value, # base64Binary
      },
      PrivateKey =>  { # ONVIF::Device::Types::BinaryData
        Data =>  $some_value, # base64Binary
      },
    },
  },,
 );

=head3 GetCertificateInformation

 A device that supports either TLS or IEEE 802.1X should support this command.

Returns a L<ONVIF::Device::Elements::GetCertificateInformationResponse|ONVIF::Device::Elements::GetCertificateInformationResponse> object.

 $response = $interface->GetCertificateInformation( {
    CertificateID =>  $some_value, # token
  },,
 );

=head3 LoadCACertificates

 A device that support either TLS or IEEE 802.1X shall support this command. As for the supported certificate format, either DER format or PEM format is possible to be used. But a device that support this command shall support at least DER format as supported format type. The device may sort the received certificate(s) based on the public key and subject information in the certificate(s). Either all CA certificates are loaded successfully or a fault message shall be returned without loading any CA certificate.

Returns a L<ONVIF::Device::Elements::LoadCACertificatesResponse|ONVIF::Device::Elements::LoadCACertificatesResponse> object.

 $response = $interface->LoadCACertificates( {
    CACertificate =>  { # ONVIF::Device::Types::Certificate
      CertificateID =>  $some_value, # token
      Certificate =>  { # ONVIF::Device::Types::BinaryData
        Data =>  $some_value, # base64Binary
      },
    },
  },,
 );

=head3 CreateDot1XConfiguration

This operation newly creates IEEE 802.1X configuration parameter set of the device. The device shall support this command if it supports IEEE 802.1X. If the device receives this request with already existing configuration token (Dot1XConfigurationToken) specification, the device should respond with 'ter:ReferenceToken ' error to indicate there is some configuration conflict.

Returns a L<ONVIF::Device::Elements::CreateDot1XConfigurationResponse|ONVIF::Device::Elements::CreateDot1XConfigurationResponse> object.

 $response = $interface->CreateDot1XConfiguration( {
    Dot1XConfiguration =>  { # ONVIF::Device::Types::Dot1XConfiguration
      Dot1XConfigurationToken => $some_value, # ReferenceToken
      Identity =>  $some_value, # string
      AnonymousID =>  $some_value, # string
      EAPMethod =>  $some_value, # int
      CACertificateID =>  $some_value, # token
      EAPMethodConfiguration =>  { # ONVIF::Device::Types::EAPMethodConfiguration
        TLSConfiguration =>  { # ONVIF::Device::Types::TLSConfiguration
          CertificateID =>  $some_value, # token
        },
        Password =>  $some_value, # string
        Extension =>  { # ONVIF::Device::Types::EapMethodExtension
        },
      },
      Extension =>  { # ONVIF::Device::Types::Dot1XConfigurationExtension
      },
    },
  },,
 );

=head3 SetDot1XConfiguration

While the CreateDot1XConfiguration command is trying to create a new configuration parameter set, this operation modifies existing IEEE 802.1X configuration parameter set of the device. A device that support IEEE 802.1X shall support this command.

Returns a L<ONVIF::Device::Elements::SetDot1XConfigurationResponse|ONVIF::Device::Elements::SetDot1XConfigurationResponse> object.

 $response = $interface->SetDot1XConfiguration( {
    Dot1XConfiguration =>  { # ONVIF::Device::Types::Dot1XConfiguration
      Dot1XConfigurationToken => $some_value, # ReferenceToken
      Identity =>  $some_value, # string
      AnonymousID =>  $some_value, # string
      EAPMethod =>  $some_value, # int
      CACertificateID =>  $some_value, # token
      EAPMethodConfiguration =>  { # ONVIF::Device::Types::EAPMethodConfiguration
        TLSConfiguration =>  { # ONVIF::Device::Types::TLSConfiguration
          CertificateID =>  $some_value, # token
        },
        Password =>  $some_value, # string
        Extension =>  { # ONVIF::Device::Types::EapMethodExtension
        },
      },
      Extension =>  { # ONVIF::Device::Types::Dot1XConfigurationExtension
      },
    },
  },,
 );

=head3 GetDot1XConfiguration

 A device that supports IEEE 802.1X shall support this command. Regardless of whether the 802.1X method in the retrieved configuration has a password or not, the device shall not include the Password element in the response.

Returns a L<ONVIF::Device::Elements::GetDot1XConfigurationResponse|ONVIF::Device::Elements::GetDot1XConfigurationResponse> object.

 $response = $interface->GetDot1XConfiguration( {
    Dot1XConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetDot1XConfigurations

 Regardless of whether the 802.1X method in the retrieved configuration has a password or not, the device shall not include the Password element in the response.

Returns a L<ONVIF::Device::Elements::GetDot1XConfigurationsResponse|ONVIF::Device::Elements::GetDot1XConfigurationsResponse> object.

 $response = $interface->GetDot1XConfigurations( {
  },,
 );

=head3 DeleteDot1XConfiguration

This operation deletes an IEEE 802.1X configuration parameter set from the device. Which configuration should be deleted is specified by the 'Dot1XConfigurationToken' in the request. A device that support IEEE 802.1X shall support this command.

Returns a L<ONVIF::Device::Elements::DeleteDot1XConfigurationResponse|ONVIF::Device::Elements::DeleteDot1XConfigurationResponse> object.

 $response = $interface->DeleteDot1XConfiguration( {
    Dot1XConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetDot11Capabilities

This operation returns the IEEE802.11 capabilities. The device shall support this operation.

Returns a L<ONVIF::Device::Elements::GetDot11CapabilitiesResponse|ONVIF::Device::Elements::GetDot11CapabilitiesResponse> object.

 $response = $interface->GetDot11Capabilities( {
  },,
 );

=head3 GetDot11Status

This operation returns the status of a wireless network interface. The device shall support this command.

Returns a L<ONVIF::Device::Elements::GetDot11StatusResponse|ONVIF::Device::Elements::GetDot11StatusResponse> object.

 $response = $interface->GetDot11Status( {
    InterfaceToken => $some_value, # ReferenceToken
  },,
 );

=head3 ScanAvailableDot11Networks

This operation returns a lists of the wireless networks in range of the device. A device should support this operation.

Returns a L<ONVIF::Device::Elements::ScanAvailableDot11NetworksResponse|ONVIF::Device::Elements::ScanAvailableDot11NetworksResponse> object.

 $response = $interface->ScanAvailableDot11Networks( {
    InterfaceToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetSystemUris

 If the device allows retrieval of system logs, support information or system backup data, it should make them available via HTTP GET. If it does, it shall support the GetSystemUris command.

Returns a L<ONVIF::Device::Elements::GetSystemUrisResponse|ONVIF::Device::Elements::GetSystemUrisResponse> object.

 $response = $interface->GetSystemUris( {
  },,
 );

=head3 StartFirmwareUpgrade

 The value of the Content-Type header in the HTTP POST request shall be “application/octetstream”.

Returns a L<ONVIF::Device::Elements::StartFirmwareUpgradeResponse|ONVIF::Device::Elements::StartFirmwareUpgradeResponse> object.

 $response = $interface->StartFirmwareUpgrade( {
  },,
 );

=head3 StartSystemRestore

 The value of the Content-Type header in the HTTP POST request shall be “application/octetstream”.

Returns a L<ONVIF::Device::Elements::StartSystemRestoreResponse|ONVIF::Device::Elements::StartSystemRestoreResponse> object.

 $response = $interface->StartSystemRestore( {
  },,
 );



=head1 AUTHOR

Generated by SOAP::WSDL on Mon Jun 30 13:36:10 2014

=cut
