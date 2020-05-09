# ==========================================================================
#
# ZoneMinder ONVIF Client module
# Copyright (C) 2014  Jan M. Hochstein
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
#
# ==========================================================================
#
# This module contains the implementation of a SOAP message parser
#

package ONVIF::Deserializer::MessageParser;
use strict; use warnings;

use SOAP::WSDL::XSD::Typelib::Builtin;
use SOAP::WSDL::XSD::Typelib::Builtin::anySimpleType;

## copied from SOAP::Constants
use constant    URI_SOAP11_ENV         => "http://schemas.xmlsoap.org/soap/envelope/";
use constant    URI_SOAP12_ENV         => "http://www.w3.org/2003/05/soap-envelope";

## copied and adapted from
use base qw(SOAP::WSDL::Expat::MessageParser); 

## we get the soap version from the message
my %soap_version_of; # :ATTR( :default<()>);

sub soap_version {
  my ($self) = @_;
  $soap_version_of{ident $self};
}

# override new() to pass along the init_args
sub new {
    my ($class, $args) = @_;
    my $self = {
        class_resolver => $args->{ class_resolver },
        strict => defined $args->{ strict } ? $args->{ strict } : 1,
    };

    bless $self, $class;

    # could be written as && - but Devel::Cover doesn't like that
    if ($args->{ class_resolver }) {
        $self->load_classes()
            if ! exists $SOAP::WSDL::Expat::MessageParser::LOADED_OF{ $self->{ class_resolver } };
    }
    return $self;
##  calling the parent's calss new() dows not work here.
#    return SOAP::WSDL::Expat::MessageParser->new($class, $args);
}

sub _initialize {
    my ($self, $parser) = @_;
    
    $self->{ parser } = $parser;

    delete $self->{ data };                     # remove potential old results
    delete $self->{ header };

    my $characters;

    # Note: $current MUST be undef - it is used as sentinel
    # on the object stack via if (! defined $list->[-1])
    # DON'T set it to anything else !
    my $current = undef;
    my $list = [];                      # node list (object stack)

    my $path = [];                      # current path
    my $skip = 0;                       # skip elements
    my $depth = 0;

    my %content_check = $self->{strict}
        ? (
            0 => sub {
                    die "Bad top node $_[1]" if $_[1] ne 'Envelope';
                    if($_[0]->namespace($_[1]) eq URI_SOAP11_ENV) {
                       $_[0]{ soap_version } = '1.1';
#                      $soap_version_of{ident $_[0]} = '1.1';
                    }
                    elsif($_[0]->namespace($_[1]) eq URI_SOAP12_ENV) {
                      $_[0]{ soap_version } = '1.2';
                    }
                    else {
                      die "Bad namespace for SOAP envelope: " . $_[0]->recognized_string();
                    }
                    #print "Receiving SOAP " . $_[0]{ soap_version } ."\n";
                    $depth++;
                    return;
            },
            1 => sub {
                    # Header or Body
                    #print "Start $_[1] at level 1\n";
                    $depth++;
                    if ($_[1] eq 'Body') {
                        if (exists $self->{ data }) { # there was header data
                            $self->{ header } = $self->{ data };
                            delete $self->{ data };
                            $list = [];
                            $path = [];
                            undef $current;
                        }
                    }
                    return;
            }
        )
        : (
            0 => sub { $depth++ },
            1 => sub { $depth++ },
        );

    # use "globals" for speed
    my ($_prefix, $_method, $_class, $_leaf) = ();

    my $char_handler = sub {
        return if (!$_leaf);    # we only want characters in leaf nodes
        $characters .= $_[1];   # add to characters
        return;                 # return void
    };

    no strict qw(refs);
    $parser->setHandlers(
        Start => sub {
            # my ($parser, $element, %attrs) = @_;

            #print "Start $_[1]\n";

            $_leaf = 1;  # believe we're a leaf node until we see an end

            # call methods without using their parameter stack
            # That's slightly faster than $content_check{ $depth }->()
            # and we don't have to pass $_[1] to the method.
            # Yup, that's dirty.
            return &{$content_check{ $depth }}
                if exists $content_check{ $depth };

            push @{ $path }, $_[1];        # step down in path
            return if $skip;               # skip inside __SKIP__

            # resolve class of this element
            $_class = $self->{ class_resolver }->get_class( $path );
            
# we cannot use this if there are <xs:any> elements
#            if (! defined($_class) and $self->{ strict }) {
#                die "Cannot resolve class for "
#                    . join('/', @{ $path }) . " via " . $self->{ class_resolver };
#            }

            if (! defined($_class) or ($_class eq '__SKIP__') ) {
                $skip = join('/', @{ $path });
                $_[0]->setHandlers( Char => undef );
                return;
            }

            # step down in tree (remember current)
            #
            # on the first object (after skipping Envelope/Body), $current
            # is undef.
            # We put it on the stack, anyway, and use it as sentinel when
            # going through the closing tags in the End handler
            #
            push @$list, $current;

            # cleanup. Mainly here to help profilers find the real hot spots
            undef $current;

            # cleanup
            $characters = q{};

            # Create and set new objects using Class::Std::Fast's object cache
            # if possible, or blessing directly into the class in question
            # (circumventing constructor) here.
            # That's dirty, but fast.
            #
            # TODO: check whether this is faster under all perls - there's
            # strange benchmark results...
            #
            # The alternative would read:
            # $current = $_class->new({ @_[2..$#_] });
            #
            $current = pop @{ $SOAP::WSDL::Expat::MessageParser::OBJECT_CACHE_REF->{ $_class } };
            if (not defined $current) {
                my $o = Class::Std::Fast::ID();
                $current = bless \$o, $_class;
            }

            # set attributes if there are any
            ATTR: {
                if (@_ > 2) {
                    # die Data::Dumper::Dumper(@_[2..$#_]);
                    my %attr = @_[2..$#_];
                    if (my $nil = delete $attr{nil}) {
                        # TODO: check namespace
                        if ($nil && $nil ne 'false') {
                            undef $characters;
                            last ATTR if not (%attr);
                        }
                    }
                    $current->attr(\%attr);
                }
            }
            $depth++;

            # TODO: Skip content of anyType / any stuff

            return;
        },

        Char => $char_handler,

        End => sub {

            #print "End $_[1]\n";
            pop @{ $path };                     # step up in path

            # check __SKIP__
            if ($skip) {
                return if $skip ne join '/', @{ $path }, $_[1];
                $skip = 0;
                $_[0]->setHandlers( Char => $char_handler );
                return;
            }

            $depth--;

            # we only set character values in leaf nodes
            if ($_leaf) {
                # Use dirty but fast access via global variables.
                #
                # The normal way (via method) would be this:
                #
                # $current->set_value( $characters ) if (length($characters));
                #
                $SOAP::WSDL::XSD::Typelib::Builtin::anySimpleType::___value
                    ->{ $$current } = $characters
                        if defined $characters && defined $current; # =~m{ [^\s] }xms;
            }

            # empty characters
            $characters = q{};

            # stop believing we're a leaf node
            $_leaf = 0;

            # return if there's only one elment - can't set it in parent ;-)
            # but set as root element if we don't have one already.
            if (not defined $list->[-1]) {
                $self->{ data } = $current if (not exists $self->{ data });
                return;
            };

            # set appropriate attribute in last element
            # multiple values must be implemented in base class
            # TODO check if hash access is faster
            # $_method = "add_$_localname";
            $_method = "add_$_[1]";
            #
            # fixup XML names for perl names
            #
            $_method =~s{\.}{__}xg;
            $_method =~s{\-}{_}xg;
            if ( $list->[-1]->can( $_method ) ) {
              $list->[-1]->$_method( $current );
              #} else {
              #print ( "ERror " . $list->[-1] . " cannot $_method\n" );
            }

            $current = pop @$list;          # step up in object hierarchy

            return;
        }
    );
    
    return $parser;
}

sub get_header {
    return $_[0]->{ header };
}

1;
