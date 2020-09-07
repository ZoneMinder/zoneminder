package ONVIF::PTZ::Interfaces::PTZ::PTZPort;
use strict;
use warnings;
use Class::Std::Fast::Storable;
use Scalar::Util qw(blessed);
use base qw(SOAP::WSDL::Client::Base);

# only load if it hasn't been loaded before
require ONVIF::PTZ::Typemaps::PTZ
    if not ONVIF::PTZ::Typemaps::PTZ->can('get_class');

sub START {
    $_[0]->set_proxy('http://www.examples.com/PTZ/') if not $_[2]->{proxy};
    $_[0]->set_class_resolver('ONVIF::PTZ::Typemaps::PTZ')
        if not $_[2]->{class_resolver};

    $_[0]->set_prefix($_[2]->{use_prefix}) if exists $_[2]->{use_prefix};
}

sub GetServiceCapabilities {
    my ($self, $body, $header) = @_;
    die "GetServiceCapabilities must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetServiceCapabilities',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/GetServiceCapabilities',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::GetServiceCapabilities )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetConfigurations {
    my ($self, $body, $header) = @_;
    die "GetConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetConfigurations',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/GetConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::GetConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetPresets {
    my ($self, $body, $header) = @_;
    die "GetPresets must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetPresets',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/GetPresets',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::GetPresets )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetPreset {
    my ($self, $body, $header) = @_;
    die "SetPreset must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetPreset',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/SetPreset',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::SetPreset )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RemovePreset {
    my ($self, $body, $header) = @_;
    die "RemovePreset must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RemovePreset',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/RemovePreset',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::RemovePreset )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GotoPreset {
    my ($self, $body, $header) = @_;
    die "GotoPreset must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GotoPreset',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/GotoPreset',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::GotoPreset )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetStatus {
    my ($self, $body, $header) = @_;
    die "GetStatus must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetStatus',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/GetStatus',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::GetStatus )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetConfiguration {
    my ($self, $body, $header) = @_;
    die "GetConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetConfiguration',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/GetConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::GetConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetNodes {
    my ($self, $body, $header) = @_;
    die "GetNodes must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetNodes',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/GetNodes',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::GetNodes )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetNode {
    my ($self, $body, $header) = @_;
    die "GetNode must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetNode',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/GetNode',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::GetNode )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetConfiguration {
    my ($self, $body, $header) = @_;
    die "SetConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetConfiguration',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/SetConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::SetConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetConfigurationOptions {
    my ($self, $body, $header) = @_;
    die "GetConfigurationOptions must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetConfigurationOptions',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/GetConfigurationOptions',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::GetConfigurationOptions )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GotoHomePosition {
    my ($self, $body, $header) = @_;
    die "GotoHomePosition must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GotoHomePosition',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/GotoHomePosition',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::GotoHomePosition )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetHomePosition {
    my ($self, $body, $header) = @_;
    die "SetHomePosition must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetHomePosition',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/SetHomePosition',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::SetHomePosition )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub ContinuousMove {
    my ($self, $body, $header) = @_;
    die "ContinuousMove must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'ContinuousMove',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/ContinuousMove',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::ContinuousMove )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RelativeMove {
    my ($self, $body, $header) = @_;
    die "RelativeMove must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RelativeMove',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/RelativeMove',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::RelativeMove )],
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
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/SendAuxiliaryCommand',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::SendAuxiliaryCommand )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub AbsoluteMove {
    my ($self, $body, $header) = @_;
    die "AbsoluteMove must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'AbsoluteMove',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/AbsoluteMove',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::AbsoluteMove )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub Stop {
    my ($self, $body, $header) = @_;
    die "Stop must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'Stop',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/Stop',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::Stop )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetPresetTours {
    my ($self, $body, $header) = @_;
    die "GetPresetTours must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetPresetTours',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/GetPresetTours',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::GetPresetTours )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetPresetTour {
    my ($self, $body, $header) = @_;
    die "GetPresetTour must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetPresetTour',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/GetPresetTour',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::GetPresetTour )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetPresetTourOptions {
    my ($self, $body, $header) = @_;
    die "GetPresetTourOptions must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetPresetTourOptions',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/GetPresetTourOptions',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::GetPresetTourOptions )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub CreatePresetTour {
    my ($self, $body, $header) = @_;
    die "CreatePresetTour must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'CreatePresetTour',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/CreatePresetTour',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::CreatePresetTour )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub ModifyPresetTour {
    my ($self, $body, $header) = @_;
    die "ModifyPresetTour must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'ModifyPresetTour',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/ModifyPresetTour',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::ModifyPresetTour )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub OperatePresetTour {
    my ($self, $body, $header) = @_;
    die "OperatePresetTour must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'OperatePresetTour',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/OperatePresetTour',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::OperatePresetTour )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RemovePresetTour {
    my ($self, $body, $header) = @_;
    die "RemovePresetTour must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RemovePresetTour',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/RemovePresetTour',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::RemovePresetTour )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetCompatibleConfigurations {
    my ($self, $body, $header) = @_;
    die "GetCompatibleConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetCompatibleConfigurations',
        soap_action => 'http://www.onvif.org/ver20/ptz/wsdl/GetCompatibleConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::PTZ::Elements::GetCompatibleConfigurations )],
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

ONVIF::PTZ::Interfaces::PTZ::PTZPort - SOAP Interface for the PTZ Web Service

=head1 SYNOPSIS

 use ONVIF::PTZ::Interfaces::PTZ::PTZPort;
 my $interface = ONVIF::PTZ::Interfaces::PTZ::PTZPort->new();

 my $response;
 $response = $interface->GetServiceCapabilities();
 $response = $interface->GetConfigurations();
 $response = $interface->GetPresets();
 $response = $interface->SetPreset();
 $response = $interface->RemovePreset();
 $response = $interface->GotoPreset();
 $response = $interface->GetStatus();
 $response = $interface->GetConfiguration();
 $response = $interface->GetNodes();
 $response = $interface->GetNode();
 $response = $interface->SetConfiguration();
 $response = $interface->GetConfigurationOptions();
 $response = $interface->GotoHomePosition();
 $response = $interface->SetHomePosition();
 $response = $interface->ContinuousMove();
 $response = $interface->RelativeMove();
 $response = $interface->SendAuxiliaryCommand();
 $response = $interface->AbsoluteMove();
 $response = $interface->Stop();
 $response = $interface->GetPresetTours();
 $response = $interface->GetPresetTour();
 $response = $interface->GetPresetTourOptions();
 $response = $interface->CreatePresetTour();
 $response = $interface->ModifyPresetTour();
 $response = $interface->OperatePresetTour();
 $response = $interface->RemovePresetTour();
 $response = $interface->GetCompatibleConfigurations();



=head1 DESCRIPTION

SOAP Interface for the PTZ web service
located at http://www.examples.com/PTZ/.

=head1 SERVICE PTZ



=head2 Port PTZPort



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

Returns the capabilities of the PTZ service. The result is returned in a typed answer.

Returns a L<ONVIF::PTZ::Elements::GetServiceCapabilitiesResponse|ONVIF::PTZ::Elements::GetServiceCapabilitiesResponse> object.

 $response = $interface->GetServiceCapabilities( {
  },,
 );

=head3 GetConfigurations

The allowed pan and tilt range for Pan/Tilt Limits is defined by a two-dimensional space range that is mapped to a specific Absolute Pan/Tilt Position Space. At least one Pan/Tilt Position Space is required by the PTZNode to support Pan/Tilt limits. The limits apply to all supported absolute, relative and continuous Pan/Tilt movements. The limits shall be checked within the coordinate system for which the limits have been specified. That means that even if movements are specified in a different coordinate system, the requested movements shall be transformed to the coordinate system of the limits where the limits can be checked. When a relative or continuous movements is specified, which would leave the specified limits, the PTZ unit has to move along the specified limits. The Zoom Limits have to be interpreted accordingly. 

Returns a L<ONVIF::PTZ::Elements::GetConfigurationsResponse|ONVIF::PTZ::Elements::GetConfigurationsResponse> object.

 $response = $interface->GetConfigurations(,,
 );

=head3 GetPresets

 Operation to request all PTZ presets for the PTZNode in the selected profile. The operation is supported if there is support for at least on PTZ preset by the PTZNode.

Returns a L<ONVIF::PTZ::Elements::GetPresetsResponse|ONVIF::PTZ::Elements::GetPresetsResponse> object.

 $response = $interface->GetPresets( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 SetPreset

The SetPreset command saves the current device position parameters so that the device can move to the saved preset position through the GotoPreset operation. In order to create a new preset, the SetPresetRequest contains no PresetToken. If creation is successful, the Response contains the PresetToken which uniquely identifies the Preset. An existing Preset can be overwritten by specifying the PresetToken of the corresponding Preset. In both cases (overwriting or creation) an optional PresetName can be specified. The operation fails if the PTZ device is moving during the SetPreset operation. The device MAY internally save additional states such as imaging properties in the PTZ Preset which then should be recalled in the GotoPreset operation. 

Returns a L<ONVIF::PTZ::Elements::SetPresetResponse|ONVIF::PTZ::Elements::SetPresetResponse> object.

 $response = $interface->SetPreset( {
    ProfileToken => $some_value, # ReferenceToken
    PresetName =>  $some_value, # string
    PresetToken => $some_value, # ReferenceToken
  },,
 );

=head3 RemovePreset

Operation to remove a PTZ preset for the Node in the selected profile. The operation is supported if the PresetPosition capability exists for the Node in the selected profile. 

Returns a L<ONVIF::PTZ::Elements::RemovePresetResponse|ONVIF::PTZ::Elements::RemovePresetResponse> object.

 $response = $interface->RemovePreset( {
    ProfileToken => $some_value, # ReferenceToken
    PresetToken => $some_value, # ReferenceToken
  },,
 );

=head3 GotoPreset

 Operation to go to a saved preset position for the PTZNode in the selected profile. The operation is supported if there is support for at least on PTZ preset by the PTZNode.

Returns a L<ONVIF::PTZ::Elements::GotoPresetResponse|ONVIF::PTZ::Elements::GotoPresetResponse> object.

 $response = $interface->GotoPreset( {
    ProfileToken => $some_value, # ReferenceToken
    PresetToken => $some_value, # ReferenceToken
    Speed =>  { # ONVIF::PTZ::Types::PTZSpeed
      PanTilt => ,
      Zoom => ,
    },
  },,
 );

=head3 GetStatus

 Operation to request PTZ status for the Node in the selected profile.

Returns a L<ONVIF::PTZ::Elements::GetStatusResponse|ONVIF::PTZ::Elements::GetStatusResponse> object.

 $response = $interface->GetStatus( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetConfiguration

The allowed pan and tilt range for Pan/Tilt Limits is defined by a two-dimensional space range that is mapped to a specific Absolute Pan/Tilt Position Space. At least one Pan/Tilt Position Space is required by the PTZNode to support Pan/Tilt limits. The limits apply to all supported absolute, relative and continuous Pan/Tilt movements. The limits shall be checked within the coordinate system for which the limits have been specified. That means that even if movements are specified in a different coordinate system, the requested movements shall be transformed to the coordinate system of the limits where the limits can be checked. When a relative or continuous movements is specified, which would leave the specified limits, the PTZ unit has to move along the specified limits. The Zoom Limits have to be interpreted accordingly. 

Returns a L<ONVIF::PTZ::Elements::GetConfigurationResponse|ONVIF::PTZ::Elements::GetConfigurationResponse> object.

 $response = $interface->GetConfiguration( {
    PTZConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetNodes

A PTZ-capable device may have multiple PTZ Nodes. The PTZ Nodes may represent mechanical PTZ drivers, uploaded PTZ drivers or digital PTZ drivers. PTZ Nodes are the lowest level entities in the PTZ control API and reflect the supported PTZ capabilities. The PTZ Node is referenced either by its name or by its reference token. 

Returns a L<ONVIF::PTZ::Elements::GetNodesResponse|ONVIF::PTZ::Elements::GetNodesResponse> object.

 $response = $interface->GetNodes(,,
 );

=head3 GetNode

Get a specific PTZ Node identified by a reference token or a name. 

Returns a L<ONVIF::PTZ::Elements::GetNodeResponse|ONVIF::PTZ::Elements::GetNodeResponse> object.

 $response = $interface->GetNode( {
    NodeToken => $some_value, # ReferenceToken
  },,
 );

=head3 SetConfiguration

Set/update a existing PTZConfiguration on the device. 

Returns a L<ONVIF::PTZ::Elements::SetConfigurationResponse|ONVIF::PTZ::Elements::SetConfigurationResponse> object.

 $response = $interface->SetConfiguration( {
    PTZConfiguration =>  { # ONVIF::PTZ::Types::PTZConfiguration
      NodeToken => $some_value, # ReferenceToken
      DefaultAbsolutePantTiltPositionSpace =>  $some_value, # anyURI
      DefaultAbsoluteZoomPositionSpace =>  $some_value, # anyURI
      DefaultRelativePanTiltTranslationSpace =>  $some_value, # anyURI
      DefaultRelativeZoomTranslationSpace =>  $some_value, # anyURI
      DefaultContinuousPanTiltVelocitySpace =>  $some_value, # anyURI
      DefaultContinuousZoomVelocitySpace =>  $some_value, # anyURI
      DefaultPTZSpeed =>  { # ONVIF::PTZ::Types::PTZSpeed
        PanTilt => ,
        Zoom => ,
      },
      DefaultPTZTimeout =>  $some_value, # duration
      PanTiltLimits =>  { # ONVIF::PTZ::Types::PanTiltLimits
        Range =>  { # ONVIF::PTZ::Types::Space2DDescription
          URI =>  $some_value, # anyURI
          XRange =>  { # ONVIF::PTZ::Types::FloatRange
            Min =>  $some_value, # float
            Max =>  $some_value, # float
          },
          YRange =>  { # ONVIF::PTZ::Types::FloatRange
            Min =>  $some_value, # float
            Max =>  $some_value, # float
          },
        },
      },
      ZoomLimits =>  { # ONVIF::PTZ::Types::ZoomLimits
        Range =>  { # ONVIF::PTZ::Types::Space1DDescription
          URI =>  $some_value, # anyURI
          XRange =>  { # ONVIF::PTZ::Types::FloatRange
            Min =>  $some_value, # float
            Max =>  $some_value, # float
          },
        },
      },
      Extension =>  { # ONVIF::PTZ::Types::PTZConfigurationExtension
        PTControlDirection =>  { # ONVIF::PTZ::Types::PTControlDirection
          EFlip =>  { # ONVIF::PTZ::Types::EFlip
            Mode => $some_value, # EFlipMode
          },
          Reverse =>  { # ONVIF::PTZ::Types::Reverse
            Mode => $some_value, # ReverseMode
          },
          Extension =>  { # ONVIF::PTZ::Types::PTControlDirectionExtension
          },
        },
        Extension =>  { # ONVIF::PTZ::Types::PTZConfigurationExtension2
        },
      },
    },
    ForcePersistence =>  $some_value, # boolean
  },,
 );

=head3 GetConfigurationOptions

List supported coordinate systems including their range limitations. Therefore, the options MAY differ depending on whether the PTZ Configuration is assigned to a Profile containing a Video Source Configuration. In that case, the options may additionally contain coordinate systems referring to the image coordinate system described by the Video Source Configuration. If the PTZ Node supports continuous movements, it shall return a Timeout Range within which Timeouts are accepted by the PTZ Node. 

Returns a L<ONVIF::PTZ::Elements::GetConfigurationOptionsResponse|ONVIF::PTZ::Elements::GetConfigurationOptionsResponse> object.

 $response = $interface->GetConfigurationOptions( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 GotoHomePosition

 Operation to move the PTZ device to it's "home" position. The operation is supported if the HomeSupported element in the PTZNode is true.

Returns a L<ONVIF::PTZ::Elements::GotoHomePositionResponse|ONVIF::PTZ::Elements::GotoHomePositionResponse> object.

 $response = $interface->GotoHomePosition( {
    ProfileToken => $some_value, # ReferenceToken
    Speed =>  { # ONVIF::PTZ::Types::PTZSpeed
      PanTilt => ,
      Zoom => ,
    },
  },,
 );

=head3 SetHomePosition

Operation to save current position as the home position. The SetHomePosition command returns with a failure if the “home” position is fixed and cannot be overwritten. If the SetHomePosition is successful, it is possible to recall the Home Position with the GotoHomePosition command.

Returns a L<ONVIF::PTZ::Elements::SetHomePositionResponse|ONVIF::PTZ::Elements::SetHomePositionResponse> object.

 $response = $interface->SetHomePosition( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 ContinuousMove

Operation for continuous Pan/Tilt and Zoom movements. The operation is supported if the PTZNode supports at least one continuous Pan/Tilt or Zoom space. If the space argument is omitted, the default space set by the PTZConfiguration will be used.

Returns a L<ONVIF::PTZ::Elements::ContinuousMoveResponse|ONVIF::PTZ::Elements::ContinuousMoveResponse> object.

 $response = $interface->ContinuousMove( {
    ProfileToken => $some_value, # ReferenceToken
    Velocity =>  { # ONVIF::PTZ::Types::PTZSpeed
      PanTilt => ,
      Zoom => ,
    },
    Timeout =>  $some_value, # duration
  },,
 );

=head3 RelativeMove

The speed argument is optional. If an x/y speed value is given it is up to the device to either use the x value as absolute resoluting speed vector or to map x and y to the component speed. If the speed argument is omitted, the default speed set by the PTZConfiguration will be used. 

Returns a L<ONVIF::PTZ::Elements::RelativeMoveResponse|ONVIF::PTZ::Elements::RelativeMoveResponse> object.

 $response = $interface->RelativeMove( {
    ProfileToken => $some_value, # ReferenceToken
    Translation =>  { # ONVIF::PTZ::Types::PTZVector
      PanTilt => ,
      Zoom => ,
    },
    Speed =>  { # ONVIF::PTZ::Types::PTZSpeed
      PanTilt => ,
      Zoom => ,
    },
  },,
 );

=head3 SendAuxiliaryCommand

Operation to send auxiliary commands to the PTZ device mapped by the PTZNode in the selected profile. The operation is supported if the AuxiliarySupported element of the PTZNode is true 

Returns a L<ONVIF::PTZ::Elements::SendAuxiliaryCommandResponse|ONVIF::PTZ::Elements::SendAuxiliaryCommandResponse> object.

 $response = $interface->SendAuxiliaryCommand( {
    ProfileToken => $some_value, # ReferenceToken
    AuxiliaryData => $some_value, # AuxiliaryData
  },,
 );

=head3 AbsoluteMove

The speed argument is optional. If an x/y speed value is given it is up to the device to either use the x value as absolute resoluting speed vector or to map x and y to the component speed. If the speed argument is omitted, the default speed set by the PTZConfiguration will be used. 

Returns a L<ONVIF::PTZ::Elements::AbsoluteMoveResponse|ONVIF::PTZ::Elements::AbsoluteMoveResponse> object.

 $response = $interface->AbsoluteMove( {
    ProfileToken => $some_value, # ReferenceToken
    Position =>  { # ONVIF::PTZ::Types::PTZVector
      PanTilt => ,
      Zoom => ,
    },
    Speed =>  { # ONVIF::PTZ::Types::PTZSpeed
      PanTilt => ,
      Zoom => ,
    },
  },,
 );

=head3 Stop

Operation to stop ongoing pan, tilt and zoom movements of absolute relative and continuous type. If no stop argument for pan, tilt or zoom is set, the device will stop all ongoing pan, tilt and zoom movements.

Returns a L<ONVIF::PTZ::Elements::StopResponse|ONVIF::PTZ::Elements::StopResponse> object.

 $response = $interface->Stop( {
    ProfileToken => $some_value, # ReferenceToken
    PanTilt =>  $some_value, # boolean
    Zoom =>  $some_value, # boolean
  },,
 );

=head3 GetPresetTours

Operation to request PTZ preset tours in the selected media profiles.

Returns a L<ONVIF::PTZ::Elements::GetPresetToursResponse|ONVIF::PTZ::Elements::GetPresetToursResponse> object.

 $response = $interface->GetPresetTours( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetPresetTour

Operation to request a specific PTZ preset tour in the selected media profile.

Returns a L<ONVIF::PTZ::Elements::GetPresetTourResponse|ONVIF::PTZ::Elements::GetPresetTourResponse> object.

 $response = $interface->GetPresetTour( {
    ProfileToken => $some_value, # ReferenceToken
    PresetTourToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetPresetTourOptions

Operation to request available options to configure PTZ preset tour.

Returns a L<ONVIF::PTZ::Elements::GetPresetTourOptionsResponse|ONVIF::PTZ::Elements::GetPresetTourOptionsResponse> object.

 $response = $interface->GetPresetTourOptions( {
    ProfileToken => $some_value, # ReferenceToken
    PresetTourToken => $some_value, # ReferenceToken
  },,
 );

=head3 CreatePresetTour

Operation to create a preset tour for the selected media profile.

Returns a L<ONVIF::PTZ::Elements::CreatePresetTourResponse|ONVIF::PTZ::Elements::CreatePresetTourResponse> object.

 $response = $interface->CreatePresetTour( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 ModifyPresetTour

Operation to modify a preset tour for the selected media profile.

Returns a L<ONVIF::PTZ::Elements::ModifyPresetTourResponse|ONVIF::PTZ::Elements::ModifyPresetTourResponse> object.

 $response = $interface->ModifyPresetTour( {
    ProfileToken => $some_value, # ReferenceToken
    PresetTour =>  { # ONVIF::PTZ::Types::PresetTour
      Name => $some_value, # Name
      Status =>  { # ONVIF::PTZ::Types::PTZPresetTourStatus
        State => $some_value, # PTZPresetTourState
        CurrentTourSpot =>  { # ONVIF::PTZ::Types::PTZPresetTourSpot
          PresetDetail =>           { # ONVIF::PTZ::Types::PTZPresetTourPresetDetail
            # One of the following elements.
            # No occurrence checks yet, so be sure to pass just one...
            PresetToken => $some_value, # ReferenceToken
            Home =>  $some_value, # boolean
            PTZPosition =>  { # ONVIF::PTZ::Types::PTZVector
              PanTilt => ,
              Zoom => ,
            },
            TypeExtension =>  { # ONVIF::PTZ::Types::PTZPresetTourTypeExtension
            },
          },
          Speed =>  { # ONVIF::PTZ::Types::PTZSpeed
            PanTilt => ,
            Zoom => ,
          },
          StayTime =>  $some_value, # duration
          Extension =>  { # ONVIF::PTZ::Types::PTZPresetTourSpotExtension
          },
        },
        Extension =>  { # ONVIF::PTZ::Types::PTZPresetTourStatusExtension
        },
      },
      AutoStart =>  $some_value, # boolean
      StartingCondition =>  { # ONVIF::PTZ::Types::PTZPresetTourStartingCondition
        RecurringTime =>  $some_value, # int
        RecurringDuration =>  $some_value, # duration
        Direction => $some_value, # PTZPresetTourDirection
        Extension =>  { # ONVIF::PTZ::Types::PTZPresetTourStartingConditionExtension
        },
      },
      TourSpot =>  { # ONVIF::PTZ::Types::PTZPresetTourSpot
        PresetDetail =>         { # ONVIF::PTZ::Types::PTZPresetTourPresetDetail
          # One of the following elements.
          # No occurrence checks yet, so be sure to pass just one...
          PresetToken => $some_value, # ReferenceToken
          Home =>  $some_value, # boolean
          PTZPosition =>  { # ONVIF::PTZ::Types::PTZVector
            PanTilt => ,
            Zoom => ,
          },
          TypeExtension =>  { # ONVIF::PTZ::Types::PTZPresetTourTypeExtension
          },
        },
        Speed =>  { # ONVIF::PTZ::Types::PTZSpeed
          PanTilt => ,
          Zoom => ,
        },
        StayTime =>  $some_value, # duration
        Extension =>  { # ONVIF::PTZ::Types::PTZPresetTourSpotExtension
        },
      },
      Extension =>  { # ONVIF::PTZ::Types::PTZPresetTourExtension
      },
    },
  },,
 );

=head3 OperatePresetTour

Operation to perform specific operation on the preset tour in selected media profile.

Returns a L<ONVIF::PTZ::Elements::OperatePresetTourResponse|ONVIF::PTZ::Elements::OperatePresetTourResponse> object.

 $response = $interface->OperatePresetTour( {
    ProfileToken => $some_value, # ReferenceToken
    PresetTourToken => $some_value, # ReferenceToken
    Operation => $some_value, # PTZPresetTourOperation
  },,
 );

=head3 RemovePresetTour

Operation to delete a specific preset tour from the media profile.

Returns a L<ONVIF::PTZ::Elements::RemovePresetTourResponse|ONVIF::PTZ::Elements::RemovePresetTourResponse> object.

 $response = $interface->RemovePresetTour( {
    ProfileToken => $some_value, # ReferenceToken
    PresetTourToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetCompatibleConfigurations

A device providing more than one PTZConfiguration or more than one VideoSourceConfiguration or which has any other resource interdependency between PTZConfiguration entities and other resources listable in a media profile should implement this operation. PTZConfiguration entities returned by this operation shall not fail on adding them to the referenced media profile. 

Returns a L<ONVIF::PTZ::Elements::GetCompatibleConfigurationsResponse|ONVIF::PTZ::Elements::GetCompatibleConfigurationsResponse> object.

 $response = $interface->GetCompatibleConfigurations( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );



=head1 AUTHOR

Generated by SOAP::WSDL on Mon Jun 30 13:37:28 2014

=cut
