import React, { useState } from 'react'
import { Combobox, ComboboxButton, ComboboxInput, ComboboxOption, ComboboxOptions } from '@headlessui/react'
import { Check, ChevronsUpDown } from 'lucide-react'
import { cn } from '@/lib/utils'

export default function SearchableSelect({ items, value, onChange, placeholder, className, disabled = false }) {
    const [query, setQuery] = useState('')

    const selectedItem = value ? items.find((item) => String(item.value) === String(value)) : null

    const filteredItems =
        query === ''
            ? items
            : items.filter((item) => {
                return item.label.toLowerCase().includes(query.toLowerCase())
            })

    return (
        <Combobox value={selectedItem} onChange={(item) => onChange(item?.value || null)} onClose={() => setQuery('')} disabled={disabled}>
            <div className={cn("relative", className)}>
                <div className={cn(
                    "relative w-full cursor-default overflow-hidden rounded-md border border-input bg-background text-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2",
                    disabled && "opacity-50 cursor-not-allowed"
                )}>
                    <ComboboxInput
                        className="w-full border-none bg-transparent py-2 pl-3 pr-10 text-sm leading-5 text-foreground focus:ring-0 focus:outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed"
                        displayValue={(item) => item?.label || ''}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder={placeholder}
                        disabled={disabled}
                    />
                    <ComboboxButton className="absolute inset-y-0 right-0 flex items-center pr-2">
                        <ChevronsUpDown className="h-4 w-4 text-muted-foreground opacity-50" aria-hidden="true" />
                    </ComboboxButton>
                </div>
                <ComboboxOptions
                    transition
                    className="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border bg-white py-1 text-base shadow-md ring-1 ring-black/5 focus:outline-none sm:text-sm empty:invisible"
                >
                    {filteredItems.length === 0 && query !== '' ? (
                        <div className="relative cursor-default select-none py-2 px-4 text-muted-foreground">
                            Nothing found.
                        </div>
                    ) : (
                        filteredItems.map((item) => (
                            <ComboboxOption
                                key={item.value}
                                value={item}
                                className={({ focus }) =>
                                    cn(
                                        "relative cursor-default select-none py-2 pl-10 pr-4",
                                        focus ? "bg-slate-100 text-slate-900" : "text-slate-900"
                                    )
                                }
                            >
                                {({ selected }) => (
                                    <>
                                        <span
                                            className={cn(
                                                "block truncate flex items-center gap-2",
                                                selected ? "font-medium" : "font-normal"
                                            )}
                                        >
                                            {item.icon && <span className="flex-shrink-0 text-muted-foreground">{item.icon}</span>}
                                            {item.label}
                                        </span>
                                        {selected ? (
                                            <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-900">
                                                <Check className="h-4 w-4" aria-hidden="true" />
                                            </span>
                                        ) : null}
                                    </>
                                )}
                            </ComboboxOption>
                        ))
                    )}
                </ComboboxOptions>
            </div>
        </Combobox>
    )
}
