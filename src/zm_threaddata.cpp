//
// ZoneMinder Explicit Thread Template Class Instantiations, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
// 
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 

void neverCalled()
{
    ThreadData<bool> dummy1;
    dummy1.setValue( false );
    dummy1.getUpdatedValue();
    dummy1.getUpdatedValue( 1 );
    dummy1.getUpdatedValue( 0.1 );
    dummy1.updateValueSignal( true );
    dummy1.updateValueBroadcast( true );

    ThreadData<int> dummy2;
    dummy2.getValue();
    dummy2.setValue( 1 );
    dummy2.getUpdatedValue( 1 );
    dummy2.updateValueBroadcast( true );
}
