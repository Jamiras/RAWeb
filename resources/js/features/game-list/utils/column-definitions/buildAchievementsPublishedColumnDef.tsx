import type { ColumnDef } from '@tanstack/react-table';
import type { RouteName } from 'ziggy-js';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';

interface BuildAchievementsPublishedColumnDefProps {
  tableApiRouteName?: RouteName;
}

export function buildAchievementsPublishedColumnDef({
  tableApiRouteName = 'api.game.index',
}: BuildAchievementsPublishedColumnDefProps): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'achievementsPublished',
    accessorKey: 'game',
    meta: { label: 'Achievements', align: 'right' },
    header: ({ column, table }) => (
      <DataTableColumnHeader
        column={column}
        table={table}
        sortType="quantity"
        tableApiRouteName={tableApiRouteName}
      />
    ),
    cell: ({ row }) => {
      const achievementsPublished = row.original.game?.achievementsPublished ?? 0;

      return (
        <p className={achievementsPublished === 0 ? 'text-muted' : ''}>{achievementsPublished}</p>
      );
    },
  };
}
