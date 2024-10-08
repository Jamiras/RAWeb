import type { ColumnFiltersState, Table } from '@tanstack/react-table';
import { RxCross2 } from 'react-icons/rx';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { formatNumber } from '@/common/utils/l10n/formatNumber';

import { getAreNonDefaultFiltersSet } from '../../utils/getAreNonDefaultFiltersSet';
import { DataTableFacetedFilter } from '../DataTableFacetedFilter';
import { DataTableSearchInput } from '../DataTableSearchInput';
import { DataTableViewOptions } from '../DataTableViewOptions';

interface AllGamesDataTableToolbarProps<TData> {
  table: Table<TData>;
  unfilteredTotal: number | null;

  defaultColumnFilters?: ColumnFiltersState;
}

export function AllGamesDataTableToolbar<TData>({
  table,
  unfilteredTotal,
  defaultColumnFilters = [],
}: AllGamesDataTableToolbarProps<TData>) {
  const { filterableSystemOptions } = usePageProps<App.Platform.Data.GameListPageProps>();

  const currentFilters = table.getState().columnFilters;
  const isFiltered = getAreNonDefaultFiltersSet(currentFilters, defaultColumnFilters);

  const resetFiltersToDefault = () => {
    if (defaultColumnFilters) {
      table.setColumnFilters(defaultColumnFilters);
    } else {
      table.resetColumnFilters();
    }
  };

  return (
    <div className="flex w-full flex-col justify-between gap-2 md:flex-row">
      <div className="flex w-full flex-col items-center gap-2 sm:flex-1 sm:flex-row">
        <DataTableSearchInput table={table} />

        {table.getColumn('system') ? (
          <DataTableFacetedFilter
            className="w-full sm:w-auto"
            column={table.getColumn('system')}
            title="System"
            options={filterableSystemOptions
              .sort((a, b) => a.name.localeCompare(b.name))
              .map((system) => ({
                label: system.name,
                selectedLabel: system.nameShort,
                value: String(system.id),
              }))}
          />
        ) : null}

        {table.getColumn('achievementsPublished') ? (
          <DataTableFacetedFilter
            className="w-full sm:w-auto"
            column={table.getColumn('achievementsPublished')}
            title="Has achievements"
            options={[
              { label: 'Yes', value: 'has' },
              { label: 'No', value: 'none' },
              { label: 'Either', value: 'either' },
            ]}
            isSearchable={false}
            isSingleSelect={true}
          />
        ) : null}

        {isFiltered ? (
          <BaseButton
            variant="ghost"
            size="sm"
            onClick={resetFiltersToDefault}
            className="border-dashed px-2 text-link lg:px-3"
          >
            Reset <RxCross2 className="ml-2 h-4 w-4" />
          </BaseButton>
        ) : null}
      </div>

      <div className="flex items-center justify-between gap-3 md:justify-normal">
        <p className="text-neutral-200 light:text-neutral-900">
          {unfilteredTotal && unfilteredTotal !== table.options.rowCount ? (
            <>
              {formatNumber(table.options.rowCount ?? 0)} of {formatNumber(unfilteredTotal)}{' '}
              {unfilteredTotal === 1 ? 'game' : 'games'}
            </>
          ) : (
            <>
              {formatNumber(table.options.rowCount ?? 0)}{' '}
              {table.options.rowCount === 1 ? 'game' : 'games'}
            </>
          )}
        </p>

        <DataTableViewOptions table={table} />
      </div>
    </div>
  );
}
