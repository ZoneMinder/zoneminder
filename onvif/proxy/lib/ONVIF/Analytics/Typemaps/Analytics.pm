
package ONVIF::Analytics::Typemaps::Analytics;
use strict;
use warnings;

our $typemap_1 = {
               'ModifyAnalyticsModules/ConfigurationToken' => 'ONVIF::Analytics::Types::ReferenceToken',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription' => 'ONVIF::Analytics::Types::ConfigDescription',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages/Data' => 'ONVIF::Analytics::Types::ItemListDescription',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Extension' => 'ONVIF::Analytics::Types::ConfigDescriptionExtension',
               'ModifyRules/Rule/Parameters/Extension' => 'ONVIF::Analytics::Types::ItemListExtension',
               'DeleteRules/RuleName' => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
               'CreateAnalyticsModules/ConfigurationToken' => 'ONVIF::Analytics::Types::ReferenceToken',
               'CreateAnalyticsModules/AnalyticsModule/Parameters/Extension' => 'ONVIF::Analytics::Types::ItemListExtension',
               'ModifyAnalyticsModules/AnalyticsModule' => 'ONVIF::Analytics::Types::Config',
               'GetSupportedRulesResponse/SupportedRules/Extension' => 'ONVIF::Analytics::Types::SupportedRulesExtension',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages/ParentTopic' => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
               'CreateRules/Rule' => 'ONVIF::Analytics::Types::Config',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Parameters/SimpleItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_SimpleItemDescription',
               'ModifyAnalyticsModules/AnalyticsModule/Parameters/SimpleItem' => 'ONVIF::Analytics::Types::ItemList::_SimpleItem',
               'CreateRules/Rule/Parameters' => 'ONVIF::Analytics::Types::ItemList',
               'GetAnalyticsModulesResponse/AnalyticsModule/Parameters/Extension' => 'ONVIF::Analytics::Types::ItemListExtension',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages/Source/SimpleItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_SimpleItemDescription',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages/Key' => 'ONVIF::Analytics::Types::ItemListDescription',
               'GetAnalyticsModulesResponse/AnalyticsModule' => 'ONVIF::Analytics::Types::Config',
               'GetServiceCapabilitiesResponse/Capabilities' => 'ONVIF::Analytics::Types::Capabilities',
               'ModifyRules/ConfigurationToken' => 'ONVIF::Analytics::Types::ReferenceToken',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages/Data/ElementItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_ElementItemDescription',
               'GetRulesResponse/Rule/Parameters' => 'ONVIF::Analytics::Types::ItemList',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages/Data/Extension' => 'ONVIF::Analytics::Types::ItemListDescriptionExtension',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages/Key/SimpleItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_SimpleItemDescription',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Parameters/ElementItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_ElementItemDescription',
               'ModifyAnalyticsModulesResponse' => 'ONVIF::Analytics::Elements::ModifyAnalyticsModulesResponse',
               'GetRulesResponse/Rule/Parameters/ElementItem' => 'ONVIF::Analytics::Types::ItemList::_ElementItem',
               'GetSupportedAnalyticsModulesResponse' => 'ONVIF::Analytics::Elements::GetSupportedAnalyticsModulesResponse',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages/Key/ElementItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_ElementItemDescription',
               'CreateRules/Rule/Parameters/Extension' => 'ONVIF::Analytics::Types::ItemListExtension',
               'GetSupportedAnalyticsModules' => 'ONVIF::Analytics::Elements::GetSupportedAnalyticsModules',
               'DeleteAnalyticsModules/ConfigurationToken' => 'ONVIF::Analytics::Types::ReferenceToken',
               'Fault/faultactor' => 'SOAP::WSDL::XSD::Typelib::Builtin::token',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleContentSchemaLocation' => 'SOAP::WSDL::XSD::Typelib::Builtin::anyURI',
               'DeleteAnalyticsModulesResponse' => 'ONVIF::Analytics::Elements::DeleteAnalyticsModulesResponse',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages/Data' => 'ONVIF::Analytics::Types::ItemListDescription',
               'Fault/faultstring' => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
               'ModifyRules/Rule/Parameters/SimpleItem' => 'ONVIF::Analytics::Types::ItemList::_SimpleItem',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Parameters' => 'ONVIF::Analytics::Types::ItemListDescription',
               'Fault' => 'SOAP::WSDL::SOAP::Typelib::Fault11',
               'CreateRules' => 'ONVIF::Analytics::Elements::CreateRules',
               'ModifyRules/Rule/Parameters' => 'ONVIF::Analytics::Types::ItemList',
               'CreateRulesResponse' => 'ONVIF::Analytics::Elements::CreateRulesResponse',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages/Key/Extension' => 'ONVIF::Analytics::Types::ItemListDescriptionExtension',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Parameters/Extension' => 'ONVIF::Analytics::Types::ItemListDescriptionExtension',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Extension' => 'ONVIF::Analytics::Types::ConfigDescriptionExtension',
               'GetAnalyticsModules/ConfigurationToken' => 'ONVIF::Analytics::Types::ReferenceToken',
               'DeleteRules/ConfigurationToken' => 'ONVIF::Analytics::Types::ReferenceToken',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages/Key/Extension' => 'ONVIF::Analytics::Types::ItemListDescriptionExtension',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages' => 'ONVIF::Analytics::Types::ConfigDescription::_Messages',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages/Key/SimpleItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_SimpleItemDescription',
               'DeleteAnalyticsModules/AnalyticsModuleName' => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
               'DeleteAnalyticsModules' => 'ONVIF::Analytics::Elements::DeleteAnalyticsModules',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages/Data/SimpleItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_SimpleItemDescription',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/Extension' => 'ONVIF::Analytics::Types::SupportedAnalyticsModulesExtension',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages/ParentTopic' => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Parameters' => 'ONVIF::Analytics::Types::ItemListDescription',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules' => 'ONVIF::Analytics::Types::SupportedAnalyticsModules',
               'GetSupportedAnalyticsModules/ConfigurationToken' => 'ONVIF::Analytics::Types::ReferenceToken',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Parameters/SimpleItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_SimpleItemDescription',
               'ModifyAnalyticsModules/AnalyticsModule/Parameters/ElementItem' => 'ONVIF::Analytics::Types::ItemList::_ElementItem',
               'Fault/detail' => 'SOAP::WSDL::XSD::Typelib::Builtin::string',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription' => 'ONVIF::Analytics::Types::ConfigDescription',
               'GetSupportedRulesResponse/SupportedRules' => 'ONVIF::Analytics::Types::SupportedRules',
               'GetSupportedRules/ConfigurationToken' => 'ONVIF::Analytics::Types::ReferenceToken',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages/Data/SimpleItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_SimpleItemDescription',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages/Data/ElementItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_ElementItemDescription',
               'GetServiceCapabilities' => 'ONVIF::Analytics::Elements::GetServiceCapabilities',
               'ModifyAnalyticsModules/AnalyticsModule/Parameters/Extension' => 'ONVIF::Analytics::Types::ItemListExtension',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages/Source/ElementItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_ElementItemDescription',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages/Source' => 'ONVIF::Analytics::Types::ItemListDescription',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages/Source/Extension' => 'ONVIF::Analytics::Types::ItemListDescriptionExtension',
               'GetAnalyticsModulesResponse' => 'ONVIF::Analytics::Elements::GetAnalyticsModulesResponse',
               'GetRulesResponse/Rule/Parameters/Extension' => 'ONVIF::Analytics::Types::ItemListExtension',
               'ModifyRules' => 'ONVIF::Analytics::Elements::ModifyRules',
               'GetRulesResponse/Rule' => 'ONVIF::Analytics::Types::Config',
               'CreateRules/Rule/Parameters/SimpleItem' => 'ONVIF::Analytics::Types::ItemList::_SimpleItem',
               'GetRules/ConfigurationToken' => 'ONVIF::Analytics::Types::ReferenceToken',
               'GetAnalyticsModulesResponse/AnalyticsModule/Parameters/SimpleItem' => 'ONVIF::Analytics::Types::ItemList::_SimpleItem',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages/Source' => 'ONVIF::Analytics::Types::ItemListDescription',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages/Extension' => 'ONVIF::Analytics::Types::MessageDescriptionExtension',
               'GetAnalyticsModulesResponse/AnalyticsModule/Parameters' => 'ONVIF::Analytics::Types::ItemList',
               'CreateRules/Rule/Parameters/ElementItem' => 'ONVIF::Analytics::Types::ItemList::_ElementItem',
               'GetRulesResponse' => 'ONVIF::Analytics::Elements::GetRulesResponse',
               'GetSupportedRules' => 'ONVIF::Analytics::Elements::GetSupportedRules',
               'GetRulesResponse/Rule/Parameters/SimpleItem' => 'ONVIF::Analytics::Types::ItemList::_SimpleItem',
               'ModifyRulesResponse' => 'ONVIF::Analytics::Elements::ModifyRulesResponse',
               'GetAnalyticsModules' => 'ONVIF::Analytics::Elements::GetAnalyticsModules',
               'ModifyRules/Rule' => 'ONVIF::Analytics::Types::Config',
               'DeleteRulesResponse' => 'ONVIF::Analytics::Elements::DeleteRulesResponse',
               'GetServiceCapabilitiesResponse' => 'ONVIF::Analytics::Elements::GetServiceCapabilitiesResponse',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages/Data/Extension' => 'ONVIF::Analytics::Types::ItemListDescriptionExtension',
               'DeleteRules' => 'ONVIF::Analytics::Elements::DeleteRules',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages' => 'ONVIF::Analytics::Types::ConfigDescription::_Messages',
               'ModifyAnalyticsModules' => 'ONVIF::Analytics::Elements::ModifyAnalyticsModules',
               'GetSupportedRulesResponse' => 'ONVIF::Analytics::Elements::GetSupportedRulesResponse',
               'CreateAnalyticsModules/AnalyticsModule' => 'ONVIF::Analytics::Types::Config',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages/Source/SimpleItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_SimpleItemDescription',
               'ModifyAnalyticsModules/AnalyticsModule/Parameters' => 'ONVIF::Analytics::Types::ItemList',
               'GetAnalyticsModulesResponse/AnalyticsModule/Parameters/ElementItem' => 'ONVIF::Analytics::Types::ItemList::_ElementItem',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Parameters/Extension' => 'ONVIF::Analytics::Types::ItemListDescriptionExtension',
               'Fault/faultcode' => 'SOAP::WSDL::XSD::Typelib::Builtin::anyURI',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages/Extension' => 'ONVIF::Analytics::Types::MessageDescriptionExtension',
               'CreateAnalyticsModules/AnalyticsModule/Parameters/ElementItem' => 'ONVIF::Analytics::Types::ItemList::_ElementItem',
               'CreateAnalyticsModulesResponse' => 'ONVIF::Analytics::Elements::CreateAnalyticsModulesResponse',
               'CreateRules/ConfigurationToken' => 'ONVIF::Analytics::Types::ReferenceToken',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages/Source/ElementItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_ElementItemDescription',
               'CreateAnalyticsModules/AnalyticsModule/Parameters' => 'ONVIF::Analytics::Types::ItemList',
               'CreateAnalyticsModules' => 'ONVIF::Analytics::Elements::CreateAnalyticsModules',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages/Source/Extension' => 'ONVIF::Analytics::Types::ItemListDescriptionExtension',
               'GetSupportedAnalyticsModulesResponse/SupportedAnalyticsModules/AnalyticsModuleDescription/Messages/Key/ElementItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_ElementItemDescription',
               'CreateAnalyticsModules/AnalyticsModule/Parameters/SimpleItem' => 'ONVIF::Analytics::Types::ItemList::_SimpleItem',
               'ModifyRules/Rule/Parameters/ElementItem' => 'ONVIF::Analytics::Types::ItemList::_ElementItem',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Parameters/ElementItemDescription' => 'ONVIF::Analytics::Types::ItemListDescription::_ElementItemDescription',
               'GetRules' => 'ONVIF::Analytics::Elements::GetRules',
               'GetSupportedRulesResponse/SupportedRules/RuleContentSchemaLocation' => 'SOAP::WSDL::XSD::Typelib::Builtin::anyURI',
               'GetSupportedRulesResponse/SupportedRules/RuleDescription/Messages/Key' => 'ONVIF::Analytics::Types::ItemListDescription'
             };
;

sub get_class {
  my $name = join '/', @{ $_[1] };
  return $typemap_1->{ $name };
}

sub get_typemap {
    return $typemap_1;
}

1;

__END__

__END__

=pod

=head1 NAME

ONVIF::Analytics::Typemaps::Analytics - typemap for Analytics

=head1 DESCRIPTION

Typemap created by SOAP::WSDL for map-based SOAP message parsers.

=cut

