#include "ni_log.h"

int ni_dev_log_level = NI_LVL_INFO;

void ni_dev_set_log_level(int level)
{
    if (level < NI_LVL_MIN)
        ni_dev_log_level = NI_LVL_MIN;
    else if (level > NI_LVL_MAX)
        ni_dev_log_level = NI_LVL_MAX;
    else
        ni_dev_log_level = level;
}
