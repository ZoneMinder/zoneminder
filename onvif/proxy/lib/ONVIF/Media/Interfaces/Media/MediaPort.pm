package ONVIF::Media::Interfaces::Media::MediaPort;
use strict;
use warnings;
use Class::Std::Fast::Storable;
use Scalar::Util qw(blessed);
use base qw(SOAP::WSDL::Client::Base);

# only load if it hasn't been loaded before
require ONVIF::Media::Typemaps::Media
    if not ONVIF::Media::Typemaps::Media->can('get_class');

sub START {
    $_[0]->set_proxy('http://www.examples.com/Media/') if not $_[2]->{proxy};
    $_[0]->set_class_resolver('ONVIF::Media::Typemaps::Media')
        if not $_[2]->{class_resolver};

    $_[0]->set_prefix($_[2]->{use_prefix}) if exists $_[2]->{use_prefix};
}

sub GetServiceCapabilities {
    my ($self, $body, $header) = @_;
    die "GetServiceCapabilities must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetServiceCapabilities',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetServiceCapabilities',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetServiceCapabilities )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetVideoSources {
    my ($self, $body, $header) = @_;
    die "GetVideoSources must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetVideoSources',
        soap_action => 'http://www.onvif.org/ver10/media/wsdlGetVideoSources/',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetVideoSources )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAudioSources {
    my ($self, $body, $header) = @_;
    die "GetAudioSources must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAudioSources',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetAudioSources',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetAudioSources )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAudioOutputs {
    my ($self, $body, $header) = @_;
    die "GetAudioOutputs must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAudioOutputs',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetAudioOutputs',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetAudioOutputs )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub CreateProfile {
    my ($self, $body, $header) = @_;
    die "CreateProfile must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'CreateProfile',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/CreateProfile',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::CreateProfile )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetProfile {
    my ($self, $body, $header) = @_;
    die "GetProfile must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetProfile',
        soap_action => 'http://www.onvif.org/ver10/media/wsdlGetProfile/',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetProfile )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetProfiles {
    my ($self, $body, $header) = @_;
    die "GetProfiles must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetProfiles',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetProfiles',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetProfiles )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub AddVideoEncoderConfiguration {
    my ($self, $body, $header) = @_;
    die "AddVideoEncoderConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'AddVideoEncoderConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/AddVideoEncoderConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::AddVideoEncoderConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub AddVideoSourceConfiguration {
    my ($self, $body, $header) = @_;
    die "AddVideoSourceConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'AddVideoSourceConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/AddVideoSourceConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::AddVideoSourceConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub AddAudioEncoderConfiguration {
    my ($self, $body, $header) = @_;
    die "AddAudioEncoderConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'AddAudioEncoderConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/AddAudioEncoderConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::AddAudioEncoderConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub AddAudioSourceConfiguration {
    my ($self, $body, $header) = @_;
    die "AddAudioSourceConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'AddAudioSourceConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/AddAudioSourceConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::AddAudioSourceConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub AddPTZConfiguration {
    my ($self, $body, $header) = @_;
    die "AddPTZConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'AddPTZConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/AddPTZConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::AddPTZConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub AddVideoAnalyticsConfiguration {
    my ($self, $body, $header) = @_;
    die "AddVideoAnalyticsConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'AddVideoAnalyticsConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/AddVideoAnalyticsConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::AddVideoAnalyticsConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub AddMetadataConfiguration {
    my ($self, $body, $header) = @_;
    die "AddMetadataConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'AddMetadataConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/AddMetadataConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::AddMetadataConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub AddAudioOutputConfiguration {
    my ($self, $body, $header) = @_;
    die "AddAudioOutputConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'AddAudioOutputConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/AddAudioOutputConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::AddAudioOutputConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub AddAudioDecoderConfiguration {
    my ($self, $body, $header) = @_;
    die "AddAudioDecoderConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'AddAudioDecoderConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/AddAudioDecoderConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::AddAudioDecoderConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RemoveVideoEncoderConfiguration {
    my ($self, $body, $header) = @_;
    die "RemoveVideoEncoderConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RemoveVideoEncoderConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/RemoveVideoEncoderConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::RemoveVideoEncoderConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RemoveVideoSourceConfiguration {
    my ($self, $body, $header) = @_;
    die "RemoveVideoSourceConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RemoveVideoSourceConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/RemoveVideoSourceConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::RemoveVideoSourceConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RemoveAudioEncoderConfiguration {
    my ($self, $body, $header) = @_;
    die "RemoveAudioEncoderConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RemoveAudioEncoderConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/RemoveAudioEncoderConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::RemoveAudioEncoderConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RemoveAudioSourceConfiguration {
    my ($self, $body, $header) = @_;
    die "RemoveAudioSourceConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RemoveAudioSourceConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/RemoveAudioSourceConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::RemoveAudioSourceConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RemovePTZConfiguration {
    my ($self, $body, $header) = @_;
    die "RemovePTZConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RemovePTZConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/RemovePTZConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::RemovePTZConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RemoveVideoAnalyticsConfiguration {
    my ($self, $body, $header) = @_;
    die "RemoveVideoAnalyticsConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RemoveVideoAnalyticsConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/RemoveVideoAnalyticsConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::RemoveVideoAnalyticsConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RemoveMetadataConfiguration {
    my ($self, $body, $header) = @_;
    die "RemoveMetadataConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RemoveMetadataConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/RemoveMetadataConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::RemoveMetadataConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RemoveAudioOutputConfiguration {
    my ($self, $body, $header) = @_;
    die "RemoveAudioOutputConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RemoveAudioOutputConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/RemoveAudioOutputConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::RemoveAudioOutputConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub RemoveAudioDecoderConfiguration {
    my ($self, $body, $header) = @_;
    die "RemoveAudioDecoderConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'RemoveAudioDecoderConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/RemoveAudioDecoderConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::RemoveAudioDecoderConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub DeleteProfile {
    my ($self, $body, $header) = @_;
    die "DeleteProfile must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'DeleteProfile',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/DeleteProfile',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::DeleteProfile )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetVideoSourceConfigurations {
    my ($self, $body, $header) = @_;
    die "GetVideoSourceConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetVideoSourceConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetVideoSourceConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetVideoSourceConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetVideoEncoderConfigurations {
    my ($self, $body, $header) = @_;
    die "GetVideoEncoderConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetVideoEncoderConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetVideoEncoderConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetVideoEncoderConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAudioSourceConfigurations {
    my ($self, $body, $header) = @_;
    die "GetAudioSourceConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAudioSourceConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdlGetAudioSourceConfigurations/',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetAudioSourceConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAudioEncoderConfigurations {
    my ($self, $body, $header) = @_;
    die "GetAudioEncoderConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAudioEncoderConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetAudioEncoderConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetAudioEncoderConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetVideoAnalyticsConfigurations {
    my ($self, $body, $header) = @_;
    die "GetVideoAnalyticsConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetVideoAnalyticsConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetVideoAnalyticsConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetVideoAnalyticsConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetMetadataConfigurations {
    my ($self, $body, $header) = @_;
    die "GetMetadataConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetMetadataConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetMetadataConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetMetadataConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAudioOutputConfigurations {
    my ($self, $body, $header) = @_;
    die "GetAudioOutputConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAudioOutputConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetAudioOutputConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetAudioOutputConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAudioDecoderConfigurations {
    my ($self, $body, $header) = @_;
    die "GetAudioDecoderConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAudioDecoderConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetAudioDecoderConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetAudioDecoderConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetVideoSourceConfiguration {
    my ($self, $body, $header) = @_;
    die "GetVideoSourceConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetVideoSourceConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetVideoSourceConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetVideoSourceConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetVideoEncoderConfiguration {
    my ($self, $body, $header) = @_;
    die "GetVideoEncoderConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetVideoEncoderConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetVideoEncoderConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetVideoEncoderConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAudioSourceConfiguration {
    my ($self, $body, $header) = @_;
    die "GetAudioSourceConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAudioSourceConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetAudioSourceConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetAudioSourceConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAudioEncoderConfiguration {
    my ($self, $body, $header) = @_;
    die "GetAudioEncoderConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAudioEncoderConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetAudioEncoderConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetAudioEncoderConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetVideoAnalyticsConfiguration {
    my ($self, $body, $header) = @_;
    die "GetVideoAnalyticsConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetVideoAnalyticsConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetVideoAnalyticsConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetVideoAnalyticsConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetMetadataConfiguration {
    my ($self, $body, $header) = @_;
    die "GetMetadataConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetMetadataConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetMetadataConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetMetadataConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAudioOutputConfiguration {
    my ($self, $body, $header) = @_;
    die "GetAudioOutputConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAudioOutputConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetAudioOutputConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetAudioOutputConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAudioDecoderConfiguration {
    my ($self, $body, $header) = @_;
    die "GetAudioDecoderConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAudioDecoderConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetAudioDecoderConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetAudioDecoderConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetCompatibleVideoEncoderConfigurations {
    my ($self, $body, $header) = @_;
    die "GetCompatibleVideoEncoderConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetCompatibleVideoEncoderConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetCompatibleVideoEncoderConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetCompatibleVideoEncoderConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetCompatibleVideoSourceConfigurations {
    my ($self, $body, $header) = @_;
    die "GetCompatibleVideoSourceConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetCompatibleVideoSourceConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetCompatibleVideoSourceConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetCompatibleVideoSourceConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetCompatibleAudioEncoderConfigurations {
    my ($self, $body, $header) = @_;
    die "GetCompatibleAudioEncoderConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetCompatibleAudioEncoderConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetCompatibleAudioEncoderConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetCompatibleAudioEncoderConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetCompatibleAudioSourceConfigurations {
    my ($self, $body, $header) = @_;
    die "GetCompatibleAudioSourceConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetCompatibleAudioSourceConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetCompatibleAudioSourceConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetCompatibleAudioSourceConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetCompatibleVideoAnalyticsConfigurations {
    my ($self, $body, $header) = @_;
    die "GetCompatibleVideoAnalyticsConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetCompatibleVideoAnalyticsConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetCompatibleVideoAnalyticsConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetCompatibleVideoAnalyticsConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetCompatibleMetadataConfigurations {
    my ($self, $body, $header) = @_;
    die "GetCompatibleMetadataConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetCompatibleMetadataConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetCompatibleMetadataConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetCompatibleMetadataConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetCompatibleAudioOutputConfigurations {
    my ($self, $body, $header) = @_;
    die "GetCompatibleAudioOutputConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetCompatibleAudioOutputConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetCompatibleAudioOutputConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetCompatibleAudioOutputConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetCompatibleAudioDecoderConfigurations {
    my ($self, $body, $header) = @_;
    die "GetCompatibleAudioDecoderConfigurations must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetCompatibleAudioDecoderConfigurations',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetCompatibleAudioDecoderConfigurations',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetCompatibleAudioDecoderConfigurations )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetVideoSourceConfiguration {
    my ($self, $body, $header) = @_;
    die "SetVideoSourceConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetVideoSourceConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/SetVideoSourceConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::SetVideoSourceConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetVideoEncoderConfiguration {
    my ($self, $body, $header) = @_;
    die "SetVideoEncoderConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetVideoEncoderConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/SetVideoEncoderConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::SetVideoEncoderConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetAudioSourceConfiguration {
    my ($self, $body, $header) = @_;
    die "SetAudioSourceConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetAudioSourceConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/SetAudioSourceConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::SetAudioSourceConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetAudioEncoderConfiguration {
    my ($self, $body, $header) = @_;
    die "SetAudioEncoderConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetAudioEncoderConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/SetAudioEncoderConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::SetAudioEncoderConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetVideoAnalyticsConfiguration {
    my ($self, $body, $header) = @_;
    die "SetVideoAnalyticsConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetVideoAnalyticsConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/SetVideoAnalyticsConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::SetVideoAnalyticsConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetMetadataConfiguration {
    my ($self, $body, $header) = @_;
    die "SetMetadataConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetMetadataConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/SetMetadataConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::SetMetadataConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetAudioOutputConfiguration {
    my ($self, $body, $header) = @_;
    die "SetAudioOutputConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetAudioOutputConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/SetAudioOutputConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::SetAudioOutputConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetAudioDecoderConfiguration {
    my ($self, $body, $header) = @_;
    die "SetAudioDecoderConfiguration must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetAudioDecoderConfiguration',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/SetAudioDecoderConfiguration',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::SetAudioDecoderConfiguration )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetVideoSourceConfigurationOptions {
    my ($self, $body, $header) = @_;
    die "GetVideoSourceConfigurationOptions must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetVideoSourceConfigurationOptions',
        soap_action => 'http://www.onvif.org/ver10/media/wsdlGetVideoSourceConfigurationOptions/',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetVideoSourceConfigurationOptions )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetVideoEncoderConfigurationOptions {
    my ($self, $body, $header) = @_;
    die "GetVideoEncoderConfigurationOptions must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetVideoEncoderConfigurationOptions',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetVideoEncoderConfigurationOptions',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetVideoEncoderConfigurationOptions )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAudioSourceConfigurationOptions {
    my ($self, $body, $header) = @_;
    die "GetAudioSourceConfigurationOptions must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAudioSourceConfigurationOptions',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetAudioSourceConfigurationOptions',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetAudioSourceConfigurationOptions )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAudioEncoderConfigurationOptions {
    my ($self, $body, $header) = @_;
    die "GetAudioEncoderConfigurationOptions must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAudioEncoderConfigurationOptions',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetAudioEncoderConfigurationOptions',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetAudioEncoderConfigurationOptions )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetMetadataConfigurationOptions {
    my ($self, $body, $header) = @_;
    die "GetMetadataConfigurationOptions must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetMetadataConfigurationOptions',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetMetadataConfigurationOptions',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetMetadataConfigurationOptions )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAudioOutputConfigurationOptions {
    my ($self, $body, $header) = @_;
    die "GetAudioOutputConfigurationOptions must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAudioOutputConfigurationOptions',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetAudioOutputConfigurationOptions',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetAudioOutputConfigurationOptions )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetAudioDecoderConfigurationOptions {
    my ($self, $body, $header) = @_;
    die "GetAudioDecoderConfigurationOptions must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetAudioDecoderConfigurationOptions',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetAudioDecoderConfigurationOptions',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetAudioDecoderConfigurationOptions )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetGuaranteedNumberOfVideoEncoderInstances {
    my ($self, $body, $header) = @_;
    die "GetGuaranteedNumberOfVideoEncoderInstances must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetGuaranteedNumberOfVideoEncoderInstances',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetGuaranteedNumberOfVideoEncoderInstances',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetGuaranteedNumberOfVideoEncoderInstances )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetStreamUri {
    my ($self, $body, $header) = @_;
    die "GetStreamUri must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetStreamUri',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetStreamUri',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetStreamUri )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub StartMulticastStreaming {
    my ($self, $body, $header) = @_;
    die "StartMulticastStreaming must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'StartMulticastStreaming',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/StartMulticastStreaming',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::StartMulticastStreaming )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub StopMulticastStreaming {
    my ($self, $body, $header) = @_;
    die "StopMulticastStreaming must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'StopMulticastStreaming',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/StopMulticastStreaming',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::StopMulticastStreaming )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetSynchronizationPoint {
    my ($self, $body, $header) = @_;
    die "SetSynchronizationPoint must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetSynchronizationPoint',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/SetSynchronizationPoint',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::SetSynchronizationPoint )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetSnapshotUri {
    my ($self, $body, $header) = @_;
    die "GetSnapshotUri must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetSnapshotUri',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetSnapshotUri',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetSnapshotUri )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetVideoSourceModes {
    my ($self, $body, $header) = @_;
    die "GetVideoSourceModes must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetVideoSourceModes',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetVideoSourceModes',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetVideoSourceModes )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetVideoSourceMode {
    my ($self, $body, $header) = @_;
    die "SetVideoSourceMode must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetVideoSourceMode',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/SetVideoSourceMode',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::SetVideoSourceMode )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetOSDs {
    my ($self, $body, $header) = @_;
    die "GetOSDs must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetOSDs',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetOSDs',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetOSDs )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetOSD {
    my ($self, $body, $header) = @_;
    die "GetOSD must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetOSD',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetOSD',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetOSD )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub GetOSDOptions {
    my ($self, $body, $header) = @_;
    die "GetOSDOptions must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'GetOSDOptions',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/GetOSDOptions',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::GetOSDOptions )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub SetOSD {
    my ($self, $body, $header) = @_;
    die "SetOSD must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'SetOSD',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/SetOSD',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::SetOSD )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub CreateOSD {
    my ($self, $body, $header) = @_;
    die "CreateOSD must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'CreateOSD',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/CreateOSD',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::CreateOSD )],
        },
        header => {
            
        },
        headerfault => {
            
        }
    }, $body, $header);
}


sub DeleteOSD {
    my ($self, $body, $header) = @_;
    die "DeleteOSD must be called as object method (\$self is <$self>)" if not blessed($self);
    return $self->SUPER::call({
        operation => 'DeleteOSD',
        soap_action => 'http://www.onvif.org/ver10/media/wsdl/DeleteOSD',
        style => 'document',
        body => {
            

           'use'            => 'literal',
            namespace       => 'http://schemas.xmlsoap.org/wsdl/soap/',
            encodingStyle   => '',
            parts           =>  [qw( ONVIF::Media::Elements::DeleteOSD )],
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

ONVIF::Media::Interfaces::Media::MediaPort - SOAP Interface for the Media Web Service

=head1 SYNOPSIS

 use ONVIF::Media::Interfaces::Media::MediaPort;
 my $interface = ONVIF::Media::Interfaces::Media::MediaPort->new();

 my $response;
 $response = $interface->GetServiceCapabilities();
 $response = $interface->GetVideoSources();
 $response = $interface->GetAudioSources();
 $response = $interface->GetAudioOutputs();
 $response = $interface->CreateProfile();
 $response = $interface->GetProfile();
 $response = $interface->GetProfiles();
 $response = $interface->AddVideoEncoderConfiguration();
 $response = $interface->AddVideoSourceConfiguration();
 $response = $interface->AddAudioEncoderConfiguration();
 $response = $interface->AddAudioSourceConfiguration();
 $response = $interface->AddPTZConfiguration();
 $response = $interface->AddVideoAnalyticsConfiguration();
 $response = $interface->AddMetadataConfiguration();
 $response = $interface->AddAudioOutputConfiguration();
 $response = $interface->AddAudioDecoderConfiguration();
 $response = $interface->RemoveVideoEncoderConfiguration();
 $response = $interface->RemoveVideoSourceConfiguration();
 $response = $interface->RemoveAudioEncoderConfiguration();
 $response = $interface->RemoveAudioSourceConfiguration();
 $response = $interface->RemovePTZConfiguration();
 $response = $interface->RemoveVideoAnalyticsConfiguration();
 $response = $interface->RemoveMetadataConfiguration();
 $response = $interface->RemoveAudioOutputConfiguration();
 $response = $interface->RemoveAudioDecoderConfiguration();
 $response = $interface->DeleteProfile();
 $response = $interface->GetVideoSourceConfigurations();
 $response = $interface->GetVideoEncoderConfigurations();
 $response = $interface->GetAudioSourceConfigurations();
 $response = $interface->GetAudioEncoderConfigurations();
 $response = $interface->GetVideoAnalyticsConfigurations();
 $response = $interface->GetMetadataConfigurations();
 $response = $interface->GetAudioOutputConfigurations();
 $response = $interface->GetAudioDecoderConfigurations();
 $response = $interface->GetVideoSourceConfiguration();
 $response = $interface->GetVideoEncoderConfiguration();
 $response = $interface->GetAudioSourceConfiguration();
 $response = $interface->GetAudioEncoderConfiguration();
 $response = $interface->GetVideoAnalyticsConfiguration();
 $response = $interface->GetMetadataConfiguration();
 $response = $interface->GetAudioOutputConfiguration();
 $response = $interface->GetAudioDecoderConfiguration();
 $response = $interface->GetCompatibleVideoEncoderConfigurations();
 $response = $interface->GetCompatibleVideoSourceConfigurations();
 $response = $interface->GetCompatibleAudioEncoderConfigurations();
 $response = $interface->GetCompatibleAudioSourceConfigurations();
 $response = $interface->GetCompatibleVideoAnalyticsConfigurations();
 $response = $interface->GetCompatibleMetadataConfigurations();
 $response = $interface->GetCompatibleAudioOutputConfigurations();
 $response = $interface->GetCompatibleAudioDecoderConfigurations();
 $response = $interface->SetVideoSourceConfiguration();
 $response = $interface->SetVideoEncoderConfiguration();
 $response = $interface->SetAudioSourceConfiguration();
 $response = $interface->SetAudioEncoderConfiguration();
 $response = $interface->SetVideoAnalyticsConfiguration();
 $response = $interface->SetMetadataConfiguration();
 $response = $interface->SetAudioOutputConfiguration();
 $response = $interface->SetAudioDecoderConfiguration();
 $response = $interface->GetVideoSourceConfigurationOptions();
 $response = $interface->GetVideoEncoderConfigurationOptions();
 $response = $interface->GetAudioSourceConfigurationOptions();
 $response = $interface->GetAudioEncoderConfigurationOptions();
 $response = $interface->GetMetadataConfigurationOptions();
 $response = $interface->GetAudioOutputConfigurationOptions();
 $response = $interface->GetAudioDecoderConfigurationOptions();
 $response = $interface->GetGuaranteedNumberOfVideoEncoderInstances();
 $response = $interface->GetStreamUri();
 $response = $interface->StartMulticastStreaming();
 $response = $interface->StopMulticastStreaming();
 $response = $interface->SetSynchronizationPoint();
 $response = $interface->GetSnapshotUri();
 $response = $interface->GetVideoSourceModes();
 $response = $interface->SetVideoSourceMode();
 $response = $interface->GetOSDs();
 $response = $interface->GetOSD();
 $response = $interface->GetOSDOptions();
 $response = $interface->SetOSD();
 $response = $interface->CreateOSD();
 $response = $interface->DeleteOSD();



=head1 DESCRIPTION

SOAP Interface for the Media web service
located at http://www.examples.com/Media/.

=head1 SERVICE Media



=head2 Port MediaPort



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

Returns the capabilities of the media service. The result is returned in a typed answer.

Returns a L<ONVIF::Media::Elements::GetServiceCapabilitiesResponse|ONVIF::Media::Elements::GetServiceCapabilitiesResponse> object.

 $response = $interface->GetServiceCapabilities( {
  },,
 );

=head3 GetVideoSources

This command lists all available physical video inputs of the device.

Returns a L<ONVIF::Media::Elements::GetVideoSourcesResponse|ONVIF::Media::Elements::GetVideoSourcesResponse> object.

 $response = $interface->GetVideoSources( {
  },,
 );

=head3 GetAudioSources

This command lists all available physical audio inputs of the device.

Returns a L<ONVIF::Media::Elements::GetAudioSourcesResponse|ONVIF::Media::Elements::GetAudioSourcesResponse> object.

 $response = $interface->GetAudioSources( {
  },,
 );

=head3 GetAudioOutputs

This command lists all available physical audio outputs of the device.

Returns a L<ONVIF::Media::Elements::GetAudioOutputsResponse|ONVIF::Media::Elements::GetAudioOutputsResponse> object.

 $response = $interface->GetAudioOutputs( {
  },,
 );

=head3 CreateProfile

This operation creates a new empty media profile. The media profile shall be created in the device and shall be persistent (remain after reboot). A created profile shall be deletable and a device shall set the “fixed” attribute to false in the returned Profile.

Returns a L<ONVIF::Media::Elements::CreateProfileResponse|ONVIF::Media::Elements::CreateProfileResponse> object.

 $response = $interface->CreateProfile( {
    Name => $some_value, # Name
    Token => $some_value, # ReferenceToken
  },,
 );

=head3 GetProfile

If the profile token is already known, a profile can be fetched through the GetProfile command.

Returns a L<ONVIF::Media::Elements::GetProfileResponse|ONVIF::Media::Elements::GetProfileResponse> object.

 $response = $interface->GetProfile( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetProfiles

Any endpoint can ask for the existing media profiles of a device using the GetProfiles command. Pre-configured or dynamically configured profiles can be retrieved using this command. This command lists all configured profiles in a device. The client does not need to know the media profile in order to use the command.

Returns a L<ONVIF::Media::Elements::GetProfilesResponse|ONVIF::Media::Elements::GetProfilesResponse> object.

 $response = $interface->GetProfiles( {
  },,
 );

=head3 AddVideoEncoderConfiguration

This operation adds a VideoEncoderConfiguration to an existing media profile. If a configuration exists in the media profile, it will be replaced. The change shall be persistent. A device shall support adding a compatible VideoEncoderConfiguration to a Profile containing a VideoSourceConfiguration and shall support streaming video data of such a profile. 

Returns a L<ONVIF::Media::Elements::AddVideoEncoderConfigurationResponse|ONVIF::Media::Elements::AddVideoEncoderConfigurationResponse> object.

 $response = $interface->AddVideoEncoderConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 AddVideoSourceConfiguration

This operation adds a VideoSourceConfiguration to an existing media profile. If such a configuration exists in the media profile, it will be replaced. The change shall be persistent.

Returns a L<ONVIF::Media::Elements::AddVideoSourceConfigurationResponse|ONVIF::Media::Elements::AddVideoSourceConfigurationResponse> object.

 $response = $interface->AddVideoSourceConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 AddAudioEncoderConfiguration

This operation adds an AudioEncoderConfiguration to an existing media profile. If a configuration exists in the media profile, it will be replaced. The change shall be persistent. A device shall support adding a compatible AudioEncoderConfiguration to a profile containing an AudioSourceConfiguration and shall support streaming audio data of such a profile. 

Returns a L<ONVIF::Media::Elements::AddAudioEncoderConfigurationResponse|ONVIF::Media::Elements::AddAudioEncoderConfigurationResponse> object.

 $response = $interface->AddAudioEncoderConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 AddAudioSourceConfiguration

This operation adds an AudioSourceConfiguration to an existing media profile. If a configuration exists in the media profile, it will be replaced. The change shall be persistent.

Returns a L<ONVIF::Media::Elements::AddAudioSourceConfigurationResponse|ONVIF::Media::Elements::AddAudioSourceConfigurationResponse> object.

 $response = $interface->AddAudioSourceConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 AddPTZConfiguration

This operation adds a PTZConfiguration to an existing media profile. If a configuration exists in the media profile, it will be replaced. The change shall be persistent. Adding a PTZConfiguration to a media profile means that streams using that media profile can contain PTZ status (in the metadata), and that the media profile can be used for controlling PTZ movement.

Returns a L<ONVIF::Media::Elements::AddPTZConfigurationResponse|ONVIF::Media::Elements::AddPTZConfigurationResponse> object.

 $response = $interface->AddPTZConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 AddVideoAnalyticsConfiguration

This operation adds a VideoAnalytics configuration to an existing media profile. If a configuration exists in the media profile, it will be replaced. The change shall be persistent. Adding a VideoAnalyticsConfiguration to a media profile means that streams using that media profile can contain video analytics data (in the metadata) as defined by the submitted configuration reference. A profile containing only a video analytics configuration but no video source configuration is incomplete. Therefore, a client should first add a video source configuration to a profile before adding a video analytics configuration. The device can deny adding of a video analytics configuration before a video source configuration.

Returns a L<ONVIF::Media::Elements::AddVideoAnalyticsConfigurationResponse|ONVIF::Media::Elements::AddVideoAnalyticsConfigurationResponse> object.

 $response = $interface->AddVideoAnalyticsConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 AddMetadataConfiguration

This operation adds a Metadata configuration to an existing media profile. If a configuration exists in the media profile, it will be replaced. The change shall be persistent. Adding a MetadataConfiguration to a Profile means that streams using that profile contain metadata. Metadata can consist of events, PTZ status, and/or video analytics data.

Returns a L<ONVIF::Media::Elements::AddMetadataConfigurationResponse|ONVIF::Media::Elements::AddMetadataConfigurationResponse> object.

 $response = $interface->AddMetadataConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 AddAudioOutputConfiguration

This operation adds an AudioOutputConfiguration to an existing media profile. If a configuration exists in the media profile, it will be replaced. The change shall be persistent.

Returns a L<ONVIF::Media::Elements::AddAudioOutputConfigurationResponse|ONVIF::Media::Elements::AddAudioOutputConfigurationResponse> object.

 $response = $interface->AddAudioOutputConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 AddAudioDecoderConfiguration

This operation adds an AudioDecoderConfiguration to an existing media profile. If a configuration exists in the media profile, it shall be replaced. The change shall be persistent.

Returns a L<ONVIF::Media::Elements::AddAudioDecoderConfigurationResponse|ONVIF::Media::Elements::AddAudioDecoderConfigurationResponse> object.

 $response = $interface->AddAudioDecoderConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 RemoveVideoEncoderConfiguration

This operation removes a VideoEncoderConfiguration from an existing media profile. If the media profile does not contain a VideoEncoderConfiguration, the operation has no effect. The removal shall be persistent.

Returns a L<ONVIF::Media::Elements::RemoveVideoEncoderConfigurationResponse|ONVIF::Media::Elements::RemoveVideoEncoderConfigurationResponse> object.

 $response = $interface->RemoveVideoEncoderConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 RemoveVideoSourceConfiguration

This operation removes a VideoSourceConfiguration from an existing media profile. If the media profile does not contain a VideoSourceConfiguration, the operation has no effect. The removal shall be persistent. Video source configurations should only be removed after removing a VideoEncoderConfiguration from the media profile.

Returns a L<ONVIF::Media::Elements::RemoveVideoSourceConfigurationResponse|ONVIF::Media::Elements::RemoveVideoSourceConfigurationResponse> object.

 $response = $interface->RemoveVideoSourceConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 RemoveAudioEncoderConfiguration

This operation removes an AudioEncoderConfiguration from an existing media profile. If the media profile does not contain an AudioEncoderConfiguration, the operation has no effect. The removal shall be persistent.

Returns a L<ONVIF::Media::Elements::RemoveAudioEncoderConfigurationResponse|ONVIF::Media::Elements::RemoveAudioEncoderConfigurationResponse> object.

 $response = $interface->RemoveAudioEncoderConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 RemoveAudioSourceConfiguration

This operation removes an AudioSourceConfiguration from an existing media profile. If the media profile does not contain an AudioSourceConfiguration, the operation has no effect. The removal shall be persistent. Audio source configurations should only be removed after removing an AudioEncoderConfiguration from the media profile.

Returns a L<ONVIF::Media::Elements::RemoveAudioSourceConfigurationResponse|ONVIF::Media::Elements::RemoveAudioSourceConfigurationResponse> object.

 $response = $interface->RemoveAudioSourceConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 RemovePTZConfiguration

This operation removes a PTZConfiguration from an existing media profile. If the media profile does not contain a PTZConfiguration, the operation has no effect. The removal shall be persistent.

Returns a L<ONVIF::Media::Elements::RemovePTZConfigurationResponse|ONVIF::Media::Elements::RemovePTZConfigurationResponse> object.

 $response = $interface->RemovePTZConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 RemoveVideoAnalyticsConfiguration

This operation removes a VideoAnalyticsConfiguration from an existing media profile. If the media profile does not contain a VideoAnalyticsConfiguration, the operation has no effect. The removal shall be persistent.

Returns a L<ONVIF::Media::Elements::RemoveVideoAnalyticsConfigurationResponse|ONVIF::Media::Elements::RemoveVideoAnalyticsConfigurationResponse> object.

 $response = $interface->RemoveVideoAnalyticsConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 RemoveMetadataConfiguration

This operation removes a MetadataConfiguration from an existing media profile. If the media profile does not contain a MetadataConfiguration, the operation has no effect. The removal shall be persistent.

Returns a L<ONVIF::Media::Elements::RemoveMetadataConfigurationResponse|ONVIF::Media::Elements::RemoveMetadataConfigurationResponse> object.

 $response = $interface->RemoveMetadataConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 RemoveAudioOutputConfiguration

This operation removes an AudioOutputConfiguration from an existing media profile. If the media profile does not contain an AudioOutputConfiguration, the operation has no effect. The removal shall be persistent.

Returns a L<ONVIF::Media::Elements::RemoveAudioOutputConfigurationResponse|ONVIF::Media::Elements::RemoveAudioOutputConfigurationResponse> object.

 $response = $interface->RemoveAudioOutputConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 RemoveAudioDecoderConfiguration

This operation removes an AudioDecoderConfiguration from an existing media profile. If the media profile does not contain an AudioDecoderConfiguration, the operation has no effect. The removal shall be persistent.

Returns a L<ONVIF::Media::Elements::RemoveAudioDecoderConfigurationResponse|ONVIF::Media::Elements::RemoveAudioDecoderConfigurationResponse> object.

 $response = $interface->RemoveAudioDecoderConfiguration( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 DeleteProfile

This operation deletes a profile. This change shall always be persistent. Deletion of a profile is only possible for non-fixed profiles

Returns a L<ONVIF::Media::Elements::DeleteProfileResponse|ONVIF::Media::Elements::DeleteProfileResponse> object.

 $response = $interface->DeleteProfile( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetVideoSourceConfigurations

This operation lists all existing video source configurations for a device. The client need not know anything about the video source configurations in order to use the command.

Returns a L<ONVIF::Media::Elements::GetVideoSourceConfigurationsResponse|ONVIF::Media::Elements::GetVideoSourceConfigurationsResponse> object.

 $response = $interface->GetVideoSourceConfigurations( {
  },,
 );

=head3 GetVideoEncoderConfigurations

This operation lists all existing video encoder configurations of a device. This command lists all configured video encoder configurations in a device. The client need not know anything apriori about the video encoder configurations in order to use the command.

Returns a L<ONVIF::Media::Elements::GetVideoEncoderConfigurationsResponse|ONVIF::Media::Elements::GetVideoEncoderConfigurationsResponse> object.

 $response = $interface->GetVideoEncoderConfigurations( {
  },,
 );

=head3 GetAudioSourceConfigurations

This operation lists all existing audio source configurations of a device. This command lists all audio source configurations in a device. The client need not know anything apriori about the audio source configurations in order to use the command.

Returns a L<ONVIF::Media::Elements::GetAudioSourceConfigurationsResponse|ONVIF::Media::Elements::GetAudioSourceConfigurationsResponse> object.

 $response = $interface->GetAudioSourceConfigurations( {
  },,
 );

=head3 GetAudioEncoderConfigurations

This operation lists all existing device audio encoder configurations. The client need not know anything apriori about the audio encoder configurations in order to use the command.

Returns a L<ONVIF::Media::Elements::GetAudioEncoderConfigurationsResponse|ONVIF::Media::Elements::GetAudioEncoderConfigurationsResponse> object.

 $response = $interface->GetAudioEncoderConfigurations( {
  },,
 );

=head3 GetVideoAnalyticsConfigurations

This operation lists all video analytics configurations of a device. This command lists all configured video analytics in a device. The client need not know anything apriori about the video analytics in order to use the command.

Returns a L<ONVIF::Media::Elements::GetVideoAnalyticsConfigurationsResponse|ONVIF::Media::Elements::GetVideoAnalyticsConfigurationsResponse> object.

 $response = $interface->GetVideoAnalyticsConfigurations( {
  },,
 );

=head3 GetMetadataConfigurations

This operation lists all existing metadata configurations. The client need not know anything apriori about the metadata in order to use the command.

Returns a L<ONVIF::Media::Elements::GetMetadataConfigurationsResponse|ONVIF::Media::Elements::GetMetadataConfigurationsResponse> object.

 $response = $interface->GetMetadataConfigurations( {
  },,
 );

=head3 GetAudioOutputConfigurations

This command lists all existing AudioOutputConfigurations of a device. The NVC need not know anything apriori about the audio configurations to use this command.

Returns a L<ONVIF::Media::Elements::GetAudioOutputConfigurationsResponse|ONVIF::Media::Elements::GetAudioOutputConfigurationsResponse> object.

 $response = $interface->GetAudioOutputConfigurations( {
  },,
 );

=head3 GetAudioDecoderConfigurations

This command lists all existing AudioDecoderConfigurations of a device. The NVC need not know anything apriori about the audio decoder configurations in order to use this command.

Returns a L<ONVIF::Media::Elements::GetAudioDecoderConfigurationsResponse|ONVIF::Media::Elements::GetAudioDecoderConfigurationsResponse> object.

 $response = $interface->GetAudioDecoderConfigurations( {
  },,
 );

=head3 GetVideoSourceConfiguration

If the video source configuration token is already known, the video source configuration can be fetched through the GetVideoSourceConfiguration command.

Returns a L<ONVIF::Media::Elements::GetVideoSourceConfigurationResponse|ONVIF::Media::Elements::GetVideoSourceConfigurationResponse> object.

 $response = $interface->GetVideoSourceConfiguration( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetVideoEncoderConfiguration

If the video encoder configuration token is already known, the encoder configuration can be fetched through the GetVideoEncoderConfiguration command.

Returns a L<ONVIF::Media::Elements::GetVideoEncoderConfigurationResponse|ONVIF::Media::Elements::GetVideoEncoderConfigurationResponse> object.

 $response = $interface->GetVideoEncoderConfiguration( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetAudioSourceConfiguration

The GetAudioSourceConfiguration command fetches the audio source configurations if the audio source configuration token is already known. An

Returns a L<ONVIF::Media::Elements::GetAudioSourceConfigurationResponse|ONVIF::Media::Elements::GetAudioSourceConfigurationResponse> object.

 $response = $interface->GetAudioSourceConfiguration( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetAudioEncoderConfiguration

The GetAudioEncoderConfiguration command fetches the encoder configuration if the audio encoder configuration token is known.

Returns a L<ONVIF::Media::Elements::GetAudioEncoderConfigurationResponse|ONVIF::Media::Elements::GetAudioEncoderConfigurationResponse> object.

 $response = $interface->GetAudioEncoderConfiguration( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetVideoAnalyticsConfiguration

The GetVideoAnalyticsConfiguration command fetches the video analytics configuration if the video analytics token is known.

Returns a L<ONVIF::Media::Elements::GetVideoAnalyticsConfigurationResponse|ONVIF::Media::Elements::GetVideoAnalyticsConfigurationResponse> object.

 $response = $interface->GetVideoAnalyticsConfiguration( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetMetadataConfiguration

The GetMetadataConfiguration command fetches the metadata configuration if the metadata token is known.

Returns a L<ONVIF::Media::Elements::GetMetadataConfigurationResponse|ONVIF::Media::Elements::GetMetadataConfigurationResponse> object.

 $response = $interface->GetMetadataConfiguration( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetAudioOutputConfiguration

If the audio output configuration token is already known, the output configuration can be fetched through the GetAudioOutputConfiguration command.

Returns a L<ONVIF::Media::Elements::GetAudioOutputConfigurationResponse|ONVIF::Media::Elements::GetAudioOutputConfigurationResponse> object.

 $response = $interface->GetAudioOutputConfiguration( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetAudioDecoderConfiguration

If the audio decoder configuration token is already known, the decoder configuration can be fetched through the GetAudioDecoderConfiguration command.

Returns a L<ONVIF::Media::Elements::GetAudioDecoderConfigurationResponse|ONVIF::Media::Elements::GetAudioDecoderConfigurationResponse> object.

 $response = $interface->GetAudioDecoderConfiguration( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetCompatibleVideoEncoderConfigurations

This operation lists all the video encoder configurations of the device that are compatible with a certain media profile. Each of the returned configurations shall be a valid input parameter for the AddVideoEncoderConfiguration command on the media profile. The result will vary depending on the capabilities, configurations and settings in the device.

Returns a L<ONVIF::Media::Elements::GetCompatibleVideoEncoderConfigurationsResponse|ONVIF::Media::Elements::GetCompatibleVideoEncoderConfigurationsResponse> object.

 $response = $interface->GetCompatibleVideoEncoderConfigurations( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetCompatibleVideoSourceConfigurations

This operation requests all the video source configurations of the device that are compatible with a certain media profile. Each of the returned configurations shall be a valid input parameter for the AddVideoSourceConfiguration command on the media profile. The result will vary depending on the capabilities, configurations and settings in the device.

Returns a L<ONVIF::Media::Elements::GetCompatibleVideoSourceConfigurationsResponse|ONVIF::Media::Elements::GetCompatibleVideoSourceConfigurationsResponse> object.

 $response = $interface->GetCompatibleVideoSourceConfigurations( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetCompatibleAudioEncoderConfigurations

This operation requests all audio encoder configurations of a device that are compatible with a certain media profile. Each of the returned configurations shall be a valid input parameter for the AddAudioSourceConfiguration command on the media profile. The result varies depending on the capabilities, configurations and settings in the device.

Returns a L<ONVIF::Media::Elements::GetCompatibleAudioEncoderConfigurationsResponse|ONVIF::Media::Elements::GetCompatibleAudioEncoderConfigurationsResponse> object.

 $response = $interface->GetCompatibleAudioEncoderConfigurations( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetCompatibleAudioSourceConfigurations

This operation requests all audio source configurations of the device that are compatible with a certain media profile. Each of the returned configurations shall be a valid input parameter for the AddAudioEncoderConfiguration command on the media profile. The result varies depending on the capabilities, configurations and settings in the device.

Returns a L<ONVIF::Media::Elements::GetCompatibleAudioSourceConfigurationsResponse|ONVIF::Media::Elements::GetCompatibleAudioSourceConfigurationsResponse> object.

 $response = $interface->GetCompatibleAudioSourceConfigurations( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetCompatibleVideoAnalyticsConfigurations

This operation requests all video analytic configurations of the device that are compatible with a certain media profile. Each of the returned configurations shall be a valid input parameter for the AddVideoAnalyticsConfiguration command on the media profile. The result varies depending on the capabilities, configurations and settings in the device.

Returns a L<ONVIF::Media::Elements::GetCompatibleVideoAnalyticsConfigurationsResponse|ONVIF::Media::Elements::GetCompatibleVideoAnalyticsConfigurationsResponse> object.

 $response = $interface->GetCompatibleVideoAnalyticsConfigurations( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetCompatibleMetadataConfigurations

This operation requests all the metadata configurations of the device that are compatible with a certain media profile. Each of the returned configurations shall be a valid input parameter for the AddMetadataConfiguration command on the media profile. The result varies depending on the capabilities, configurations and settings in the device.

Returns a L<ONVIF::Media::Elements::GetCompatibleMetadataConfigurationsResponse|ONVIF::Media::Elements::GetCompatibleMetadataConfigurationsResponse> object.

 $response = $interface->GetCompatibleMetadataConfigurations( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetCompatibleAudioOutputConfigurations

This command lists all audio output configurations of a device that are compatible with a certain media profile. Each returned configuration shall be a valid input for the AddAudioOutputConfiguration command.

Returns a L<ONVIF::Media::Elements::GetCompatibleAudioOutputConfigurationsResponse|ONVIF::Media::Elements::GetCompatibleAudioOutputConfigurationsResponse> object.

 $response = $interface->GetCompatibleAudioOutputConfigurations( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetCompatibleAudioDecoderConfigurations

This operation lists all the audio decoder configurations of the device that are compatible with a certain media profile. Each of the returned configurations shall be a valid input parameter for the AddAudioDecoderConfiguration command on the media profile.

Returns a L<ONVIF::Media::Elements::GetCompatibleAudioDecoderConfigurationsResponse|ONVIF::Media::Elements::GetCompatibleAudioDecoderConfigurationsResponse> object.

 $response = $interface->GetCompatibleAudioDecoderConfigurations( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 SetVideoSourceConfiguration

This operation modifies a video source configuration. The ForcePersistence flag indicates if the changes shall remain after reboot of the device. Running streams using this configuration may be immediately updated according to the new settings. The changes are not guaranteed to take effect unless the client requests a new stream URI and restarts any affected stream. NVC methods for changing a running stream are out of scope for this specification.

Returns a L<ONVIF::Media::Elements::SetVideoSourceConfigurationResponse|ONVIF::Media::Elements::SetVideoSourceConfigurationResponse> object.

 $response = $interface->SetVideoSourceConfiguration( {
    Configuration =>  { # ONVIF::Media::Types::VideoSourceConfiguration
      SourceToken => $some_value, # ReferenceToken
      Bounds => ,
      Extension =>  { # ONVIF::Media::Types::VideoSourceConfigurationExtension
        Rotate =>  { # ONVIF::Media::Types::Rotate
          Mode => $some_value, # RotateMode
          Degree =>  $some_value, # int
          Extension =>  { # ONVIF::Media::Types::RotateExtension
          },
        },
        Extension =>  { # ONVIF::Media::Types::VideoSourceConfigurationExtension2
        },
      },
    },
    ForcePersistence =>  $some_value, # boolean
  },,
 );

=head3 SetVideoEncoderConfiguration

SessionTimeout is provided as a hint for keeping rtsp session by a device. If necessary the device may adapt parameter values for SessionTimeout elements without returning an error. For the time between keep alive calls the client shall adhere to the timeout value signaled via RTSP.

Returns a L<ONVIF::Media::Elements::SetVideoEncoderConfigurationResponse|ONVIF::Media::Elements::SetVideoEncoderConfigurationResponse> object.

 $response = $interface->SetVideoEncoderConfiguration( {
    Configuration =>  { # ONVIF::Media::Types::VideoEncoderConfiguration
      Encoding => $some_value, # VideoEncoding
      Resolution =>  { # ONVIF::Media::Types::VideoResolution
        Width =>  $some_value, # int
        Height =>  $some_value, # int
      },
      Quality =>  $some_value, # float
      RateControl =>  { # ONVIF::Media::Types::VideoRateControl
        FrameRateLimit =>  $some_value, # int
        EncodingInterval =>  $some_value, # int
        BitrateLimit =>  $some_value, # int
      },
      MPEG4 =>  { # ONVIF::Media::Types::Mpeg4Configuration
        GovLength =>  $some_value, # int
        Mpeg4Profile => $some_value, # Mpeg4Profile
      },
      H264 =>  { # ONVIF::Media::Types::H264Configuration
        GovLength =>  $some_value, # int
        H264Profile => $some_value, # H264Profile
      },
      Multicast =>  { # ONVIF::Media::Types::MulticastConfiguration
        Address =>  { # ONVIF::Media::Types::IPAddress
          Type => $some_value, # IPType
          IPv4Address => $some_value, # IPv4Address
          IPv6Address => $some_value, # IPv6Address
        },
        Port =>  $some_value, # int
        TTL =>  $some_value, # int
        AutoStart =>  $some_value, # boolean
      },
      SessionTimeout =>  $some_value, # duration
    },
    ForcePersistence =>  $some_value, # boolean
  },,
 );

=head3 SetAudioSourceConfiguration

This operation modifies an audio source configuration. The ForcePersistence flag indicates if the changes shall remain after reboot of the device. Running streams using this configuration may be immediately updated according to the new settings. The changes are not guaranteed to take effect unless the client requests a new stream URI and restarts any affected stream NVC methods for changing a running stream are out of scope for this specification.

Returns a L<ONVIF::Media::Elements::SetAudioSourceConfigurationResponse|ONVIF::Media::Elements::SetAudioSourceConfigurationResponse> object.

 $response = $interface->SetAudioSourceConfiguration( {
    Configuration =>  { # ONVIF::Media::Types::AudioSourceConfiguration
      SourceToken => $some_value, # ReferenceToken
    },
    ForcePersistence =>  $some_value, # boolean
  },,
 );

=head3 SetAudioEncoderConfiguration

This operation modifies an audio encoder configuration. The ForcePersistence flag indicates if the changes shall remain after reboot of the device. Running streams using this configuration may be immediately updated according to the new settings. The changes are not guaranteed to take effect unless the client requests a new stream URI and restarts any affected streams. NVC methods for changing a running stream are out of scope for this specification.

Returns a L<ONVIF::Media::Elements::SetAudioEncoderConfigurationResponse|ONVIF::Media::Elements::SetAudioEncoderConfigurationResponse> object.

 $response = $interface->SetAudioEncoderConfiguration( {
    Configuration =>  { # ONVIF::Media::Types::AudioEncoderConfiguration
      Encoding => $some_value, # AudioEncoding
      Bitrate =>  $some_value, # int
      SampleRate =>  $some_value, # int
      Multicast =>  { # ONVIF::Media::Types::MulticastConfiguration
        Address =>  { # ONVIF::Media::Types::IPAddress
          Type => $some_value, # IPType
          IPv4Address => $some_value, # IPv4Address
          IPv6Address => $some_value, # IPv6Address
        },
        Port =>  $some_value, # int
        TTL =>  $some_value, # int
        AutoStart =>  $some_value, # boolean
      },
      SessionTimeout =>  $some_value, # duration
    },
    ForcePersistence =>  $some_value, # boolean
  },,
 );

=head3 SetVideoAnalyticsConfiguration

A video analytics configuration is modified using this command. The ForcePersistence flag indicates if the changes shall remain after reboot of the device or not. Running streams using this configuration shall be immediately updated according to the new settings. Otherwise inconsistencies can occur between the scene description processed by the rule engine and the notifications produced by analytics engine and rule engine which reference the very same video analytics configuration token.

Returns a L<ONVIF::Media::Elements::SetVideoAnalyticsConfigurationResponse|ONVIF::Media::Elements::SetVideoAnalyticsConfigurationResponse> object.

 $response = $interface->SetVideoAnalyticsConfiguration( {
    Configuration =>  { # ONVIF::Media::Types::VideoAnalyticsConfiguration
      AnalyticsEngineConfiguration =>  { # ONVIF::Media::Types::AnalyticsEngineConfiguration
        AnalyticsModule =>  { # ONVIF::Media::Types::Config
          Parameters =>  { # ONVIF::Media::Types::ItemList
            SimpleItem => ,
            ElementItem =>  {
            },
            Extension =>  { # ONVIF::Media::Types::ItemListExtension
            },
          },
        },
        Extension =>  { # ONVIF::Media::Types::AnalyticsEngineConfigurationExtension
        },
      },
      RuleEngineConfiguration =>  { # ONVIF::Media::Types::RuleEngineConfiguration
        Rule =>  { # ONVIF::Media::Types::Config
          Parameters =>  { # ONVIF::Media::Types::ItemList
            SimpleItem => ,
            ElementItem =>  {
            },
            Extension =>  { # ONVIF::Media::Types::ItemListExtension
            },
          },
        },
        Extension =>  { # ONVIF::Media::Types::RuleEngineConfigurationExtension
        },
      },
    },
    ForcePersistence =>  $some_value, # boolean
  },,
 );

=head3 SetMetadataConfiguration

This operation modifies a metadata configuration. The ForcePersistence flag indicates if the changes shall remain after reboot of the device. Changes in the Multicast settings shall always be persistent. Running streams using this configuration may be updated immediately according to the new settings. The changes are not guaranteed to take effect unless the client requests a new stream URI and restarts any affected streams. NVC methods for changing a running stream are out of scope for this specification.

Returns a L<ONVIF::Media::Elements::SetMetadataConfigurationResponse|ONVIF::Media::Elements::SetMetadataConfigurationResponse> object.

 $response = $interface->SetMetadataConfiguration( {
    Configuration =>  { # ONVIF::Media::Types::MetadataConfiguration
      PTZStatus =>  { # ONVIF::Media::Types::PTZFilter
        Status =>  $some_value, # boolean
        Position =>  $some_value, # boolean
      },
      Analytics =>  $some_value, # boolean
      Multicast =>  { # ONVIF::Media::Types::MulticastConfiguration
        Address =>  { # ONVIF::Media::Types::IPAddress
          Type => $some_value, # IPType
          IPv4Address => $some_value, # IPv4Address
          IPv6Address => $some_value, # IPv6Address
        },
        Port =>  $some_value, # int
        TTL =>  $some_value, # int
        AutoStart =>  $some_value, # boolean
      },
      SessionTimeout =>  $some_value, # duration
      AnalyticsEngineConfiguration =>  { # ONVIF::Media::Types::AnalyticsEngineConfiguration
        AnalyticsModule =>  { # ONVIF::Media::Types::Config
          Parameters =>  { # ONVIF::Media::Types::ItemList
            SimpleItem => ,
            ElementItem =>  {
            },
            Extension =>  { # ONVIF::Media::Types::ItemListExtension
            },
          },
        },
        Extension =>  { # ONVIF::Media::Types::AnalyticsEngineConfigurationExtension
        },
      },
      Extension =>  { # ONVIF::Media::Types::MetadataConfigurationExtension
      },
    },
    ForcePersistence =>  $some_value, # boolean
  },,
 );

=head3 SetAudioOutputConfiguration

This operation modifies an audio output configuration. The ForcePersistence flag indicates if the changes shall remain after reboot of the device.

Returns a L<ONVIF::Media::Elements::SetAudioOutputConfigurationResponse|ONVIF::Media::Elements::SetAudioOutputConfigurationResponse> object.

 $response = $interface->SetAudioOutputConfiguration( {
    Configuration =>  { # ONVIF::Media::Types::AudioOutputConfiguration
      OutputToken => $some_value, # ReferenceToken
      SendPrimacy =>  $some_value, # anyURI
      OutputLevel =>  $some_value, # int
    },
    ForcePersistence =>  $some_value, # boolean
  },,
 );

=head3 SetAudioDecoderConfiguration

This operation modifies an audio decoder configuration. The ForcePersistence flag indicates if the changes shall remain after reboot of the device.

Returns a L<ONVIF::Media::Elements::SetAudioDecoderConfigurationResponse|ONVIF::Media::Elements::SetAudioDecoderConfigurationResponse> object.

 $response = $interface->SetAudioDecoderConfiguration( {
    Configuration =>  { # ONVIF::Media::Types::AudioDecoderConfiguration
    },
    ForcePersistence =>  $some_value, # boolean
  },,
 );

=head3 GetVideoSourceConfigurationOptions

This operation returns the available options (supported values and ranges for video source configuration parameters) when the video source parameters are reconfigured If a video source configuration is specified, the options shall concern that particular configuration. If a media profile is specified, the options shall be compatible with that media profile.

Returns a L<ONVIF::Media::Elements::GetVideoSourceConfigurationOptionsResponse|ONVIF::Media::Elements::GetVideoSourceConfigurationOptionsResponse> object.

 $response = $interface->GetVideoSourceConfigurationOptions( {
    ConfigurationToken => $some_value, # ReferenceToken
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetVideoEncoderConfigurationOptions

This response contains the available video encoder configuration options. If a video encoder configuration is specified, the options shall concern that particular configuration. If a media profile is specified, the options shall be compatible with that media profile. If no tokens are specified, the options shall be considered generic for the device. 

Returns a L<ONVIF::Media::Elements::GetVideoEncoderConfigurationOptionsResponse|ONVIF::Media::Elements::GetVideoEncoderConfigurationOptionsResponse> object.

 $response = $interface->GetVideoEncoderConfigurationOptions( {
    ConfigurationToken => $some_value, # ReferenceToken
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetAudioSourceConfigurationOptions

This operation returns the available options (supported values and ranges for audio source configuration parameters) when the audio source parameters are reconfigured. If an audio source configuration is specified, the options shall concern that particular configuration. If a media profile is specified, the options shall be compatible with that media profile.

Returns a L<ONVIF::Media::Elements::GetAudioSourceConfigurationOptionsResponse|ONVIF::Media::Elements::GetAudioSourceConfigurationOptionsResponse> object.

 $response = $interface->GetAudioSourceConfigurationOptions( {
    ConfigurationToken => $some_value, # ReferenceToken
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetAudioEncoderConfigurationOptions

This operation returns the available options (supported values and ranges for audio encoder configuration parameters) when the audio encoder parameters are reconfigured.

Returns a L<ONVIF::Media::Elements::GetAudioEncoderConfigurationOptionsResponse|ONVIF::Media::Elements::GetAudioEncoderConfigurationOptionsResponse> object.

 $response = $interface->GetAudioEncoderConfigurationOptions( {
    ConfigurationToken => $some_value, # ReferenceToken
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetMetadataConfigurationOptions

This operation returns the available options (supported values and ranges for metadata configuration parameters) for changing the metadata configuration.

Returns a L<ONVIF::Media::Elements::GetMetadataConfigurationOptionsResponse|ONVIF::Media::Elements::GetMetadataConfigurationOptionsResponse> object.

 $response = $interface->GetMetadataConfigurationOptions( {
    ConfigurationToken => $some_value, # ReferenceToken
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetAudioOutputConfigurationOptions

This operation returns the available options (supported values and ranges for audio output configuration parameters) for configuring an audio output.

Returns a L<ONVIF::Media::Elements::GetAudioOutputConfigurationOptionsResponse|ONVIF::Media::Elements::GetAudioOutputConfigurationOptionsResponse> object.

 $response = $interface->GetAudioOutputConfigurationOptions( {
    ConfigurationToken => $some_value, # ReferenceToken
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetAudioDecoderConfigurationOptions

This command list the audio decoding capabilities for a given profile and configuration of a device.

Returns a L<ONVIF::Media::Elements::GetAudioDecoderConfigurationOptionsResponse|ONVIF::Media::Elements::GetAudioDecoderConfigurationOptionsResponse> object.

 $response = $interface->GetAudioDecoderConfigurationOptions( {
    ConfigurationToken => $some_value, # ReferenceToken
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetGuaranteedNumberOfVideoEncoderInstances

The GetGuaranteedNumberOfVideoEncoderInstances command can be used to request the minimum number of guaranteed video encoder instances (applications) per Video Source Configuration.

Returns a L<ONVIF::Media::Elements::GetGuaranteedNumberOfVideoEncoderInstancesResponse|ONVIF::Media::Elements::GetGuaranteedNumberOfVideoEncoderInstancesResponse> object.

 $response = $interface->GetGuaranteedNumberOfVideoEncoderInstances( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetStreamUri

 For full compatibility with other ONVIF services a device should not generate Uris longer than 128 octets.

Returns a L<ONVIF::Media::Elements::GetStreamUriResponse|ONVIF::Media::Elements::GetStreamUriResponse> object.

 $response = $interface->GetStreamUri( {
    StreamSetup =>  { # ONVIF::Media::Types::StreamSetup
      Stream => $some_value, # StreamType
      Transport =>  { # ONVIF::Media::Types::Transport
        Protocol => $some_value, # TransportProtocol
      },
    },
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 StartMulticastStreaming

This command starts multicast streaming using a specified media profile of a device. Streaming continues until StopMulticastStreaming is called for the same Profile. The streaming shall continue after a reboot of the device until a StopMulticastStreaming request is received. The multicast address, port and TTL are configured in the VideoEncoderConfiguration, AudioEncoderConfiguration and MetadataConfiguration respectively.

Returns a L<ONVIF::Media::Elements::StartMulticastStreamingResponse|ONVIF::Media::Elements::StartMulticastStreamingResponse> object.

 $response = $interface->StartMulticastStreaming( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 StopMulticastStreaming

This command stop multicast streaming using a specified media profile of a device

Returns a L<ONVIF::Media::Elements::StopMulticastStreamingResponse|ONVIF::Media::Elements::StopMulticastStreamingResponse> object.

 $response = $interface->StopMulticastStreaming( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 SetSynchronizationPoint

Synchronization points allow clients to decode and correctly use all data after the synchronization point. For example, if a video stream is configured with a large I-frame distance and a client loses a single packet, the client does not display video until the next I-frame is transmitted. In such cases, the client can request a Synchronization Point which enforces the device to add an I-Frame as soon as possible. Clients can request Synchronization Points for profiles. The device shall add synchronization points for all streams associated with this profile. Similarly, a synchronization point is used to get an update on full PTZ or event status through the metadata stream. If a video stream is associated with the profile, an I-frame shall be added to this video stream. If a PTZ metadata stream is associated to the profile, the PTZ position shall be repeated within the metadata stream.

Returns a L<ONVIF::Media::Elements::SetSynchronizationPointResponse|ONVIF::Media::Elements::SetSynchronizationPointResponse> object.

 $response = $interface->SetSynchronizationPoint( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetSnapshotUri

A client uses the GetSnapshotUri command to obtain a JPEG snapshot from the device. The returned URI shall remain valid indefinitely even if the profile is changed. The ValidUntilConnect, ValidUntilReboot and Timeout Parameter shall be set accordingly (ValidUntilConnect=false, ValidUntilReboot=false, timeout=PT0S). The URI can be used for acquiring a JPEG image through a HTTP GET operation. The image encoding will always be JPEG regardless of the encoding setting in the media profile. The Jpeg settings (like resolution or quality) may be taken from the profile if suitable. The provided image will be updated automatically and independent from calls to GetSnapshotUri.

Returns a L<ONVIF::Media::Elements::GetSnapshotUriResponse|ONVIF::Media::Elements::GetSnapshotUriResponse> object.

 $response = $interface->GetSnapshotUri( {
    ProfileToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetVideoSourceModes

A device returns the information for current video source mode and settable video source modes of specified video source. A device that indicates a capability of VideoSourceModes shall support this command.

Returns a L<ONVIF::Media::Elements::GetVideoSourceModesResponse|ONVIF::Media::Elements::GetVideoSourceModesResponse> object.

 $response = $interface->GetVideoSourceModes( {
    VideoSourceToken => $some_value, # ReferenceToken
  },,
 );

=head3 SetVideoSourceMode

SetVideoSourceMode changes the media profile structure relating to video source for the specified video source mode. A device that indicates a capability of VideoSourceModes shall support this command. The behavior after changing the mode is not defined in this specification.

Returns a L<ONVIF::Media::Elements::SetVideoSourceModeResponse|ONVIF::Media::Elements::SetVideoSourceModeResponse> object.

 $response = $interface->SetVideoSourceMode( {
    VideoSourceToken => $some_value, # ReferenceToken
    VideoSourceModeToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetOSDs

Get the OSDs.

Returns a L<ONVIF::Media::Elements::GetOSDsResponse|ONVIF::Media::Elements::GetOSDsResponse> object.

 $response = $interface->GetOSDs( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetOSD

Get the OSD.

Returns a L<ONVIF::Media::Elements::GetOSDResponse|ONVIF::Media::Elements::GetOSDResponse> object.

 $response = $interface->GetOSD( {
    OSDToken => $some_value, # ReferenceToken
  },,
 );

=head3 GetOSDOptions

Get the OSD Options.

Returns a L<ONVIF::Media::Elements::GetOSDOptionsResponse|ONVIF::Media::Elements::GetOSDOptionsResponse> object.

 $response = $interface->GetOSDOptions( {
    ConfigurationToken => $some_value, # ReferenceToken
  },,
 );

=head3 SetOSD

Set the OSD

Returns a L<ONVIF::Media::Elements::SetOSDResponse|ONVIF::Media::Elements::SetOSDResponse> object.

 $response = $interface->SetOSD( {
    OSD =>  { # ONVIF::Media::Types::OSDConfiguration
      VideoSourceConfigurationToken =>  { value => $some_value },
      Type => $some_value, # OSDType
      Position =>  { # ONVIF::Media::Types::OSDPosConfiguration
        Type =>  $some_value, # string
        Pos => ,
        Extension =>  { # ONVIF::Media::Types::OSDPosConfigurationExtension
        },
      },
      TextString =>  { # ONVIF::Media::Types::OSDTextConfiguration
        Type =>  $some_value, # string
        DateFormat =>  $some_value, # string
        TimeFormat =>  $some_value, # string
        FontSize =>  $some_value, # int
        FontColor =>  { # ONVIF::Media::Types::OSDColor
          Color => ,
        },
        BackgroundColor =>  { # ONVIF::Media::Types::OSDColor
          Color => ,
        },
        PlainText =>  $some_value, # string
        Extension =>  { # ONVIF::Media::Types::OSDTextConfigurationExtension
        },
      },
      Image =>  { # ONVIF::Media::Types::OSDImgConfiguration
        ImgPath =>  $some_value, # anyURI
        Extension =>  { # ONVIF::Media::Types::OSDImgConfigurationExtension
        },
      },
      Extension =>  { # ONVIF::Media::Types::OSDConfigurationExtension
      },
    },
  },,
 );

=head3 CreateOSD

Create the OSD.

Returns a L<ONVIF::Media::Elements::CreateOSDResponse|ONVIF::Media::Elements::CreateOSDResponse> object.

 $response = $interface->CreateOSD( {
    OSD =>  { # ONVIF::Media::Types::OSDConfiguration
      VideoSourceConfigurationToken =>  { value => $some_value },
      Type => $some_value, # OSDType
      Position =>  { # ONVIF::Media::Types::OSDPosConfiguration
        Type =>  $some_value, # string
        Pos => ,
        Extension =>  { # ONVIF::Media::Types::OSDPosConfigurationExtension
        },
      },
      TextString =>  { # ONVIF::Media::Types::OSDTextConfiguration
        Type =>  $some_value, # string
        DateFormat =>  $some_value, # string
        TimeFormat =>  $some_value, # string
        FontSize =>  $some_value, # int
        FontColor =>  { # ONVIF::Media::Types::OSDColor
          Color => ,
        },
        BackgroundColor =>  { # ONVIF::Media::Types::OSDColor
          Color => ,
        },
        PlainText =>  $some_value, # string
        Extension =>  { # ONVIF::Media::Types::OSDTextConfigurationExtension
        },
      },
      Image =>  { # ONVIF::Media::Types::OSDImgConfiguration
        ImgPath =>  $some_value, # anyURI
        Extension =>  { # ONVIF::Media::Types::OSDImgConfigurationExtension
        },
      },
      Extension =>  { # ONVIF::Media::Types::OSDConfigurationExtension
      },
    },
  },,
 );

=head3 DeleteOSD

Delete the OSD.

Returns a L<ONVIF::Media::Elements::DeleteOSDResponse|ONVIF::Media::Elements::DeleteOSDResponse> object.

 $response = $interface->DeleteOSD( {
    OSDToken => $some_value, # ReferenceToken
  },,
 );



=head1 AUTHOR

Generated by SOAP::WSDL on Mon Jun 30 13:26:09 2014

=cut
