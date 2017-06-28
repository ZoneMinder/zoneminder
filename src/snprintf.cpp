snprintf( swap_path, sizeof(swap_path), "%s/zmswap-m%d/zmswap-q%06d", staticConfig.PATH_SWAP.c_str(), monitor->Id(), connkey );

int len = snprintf(NULL, 0, "/zmswap-m%d", monitor->Id());


  int swap_path_length = strlen(staticConfig.PATH_SWAP.c_str()) + snprintf(NULL, 0, "/zmswap-m%d", monitor->Id() ) + snprintf(NULL, 0, "/zmswap-q%06d", connkey ) + 1; // +1 for NULL terminator

  if ( connkey && playback_buffer > 0 ) {

    if ( swap_path_length + max_swap_len_suffix > PATH_MAX ) {
      Error( "Swap Path is too long. %d > %d ", swap_path_length+max_swap_len_suffix, PATH_MAX );
    } else {
      swap_path = (char *)malloc( swap_path_length+max_swap_len_suffix );
      Debug( 3, "Checking swap image path %s", staticConfig.PATH_SWAP.c_str() );
      strncpy( swap_path, staticConfig.PATH_SWAP.c_str(), swap_path_length );
      if ( checkSwapPath( swap_path, false ) ) {
        snprintf( &(swap_path[swap_path_length]), max_swap_len_suffix, "/zmswap-m%d", monitor->Id() );
        if ( checkSwapPath( swap_path, true ) ) {
          snprintf( &(swap_path[swap_path_length]), max_swap_len_suffix, "/zmswap-q%06d", connkey );
          if ( checkSwapPath( swap_path, true ) ) {
            buffered_playback = true;
          }
        }
      }

