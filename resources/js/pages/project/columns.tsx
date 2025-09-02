"use client"

import { ColumnDef } from "@tanstack/react-table"
import { type Project } from "@/types";
import { Button } from "@/components/ui/button"
import { MoreHorizontal } from "lucide-react"

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"

export const columns: ColumnDef<Project>[] = [
    {
    accessorKey: "name",
     header: () => <div className="text-right">Nom</div>,
     cell: ({ row }) => {
      const nom = parseFloat(row.getValue("name"))
      
      return <div className="text-right font-medium">{nom}</div>
    },
  },
  {
    accessorKey: "description",
    header: () => <div className="text-right">Nom</div>,
    cell: ({ row }) => {
      const desc = parseFloat(row.getValue("description"))
      
      return <div className="text-right font-medium">{desc}</div>
    },
  },
  {
    accessorKey: "status",
    header: () => <div className="text-right">Nom</div>,
    cell: ({ row }) => {
      const status = parseFloat(row.getValue("status"))
      
      return <div className="text-right font-medium">{status}</div>
    },
  },
  {
    accessorKey: "end_date",
    header: () => <div className="text-right">Nom</div>,
    cell: ({ row }) => {
      const end_date = parseFloat(row.getValue("end_date"))
      
      return <div className="text-right font-medium">{end_date}</div>
    },
  },
  {
    id: "actions",
    cell: ({ row }) => {
      const Project = row.original
 
      return (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" className="h-8 w-8 p-0">
              <span className="sr-only">Open menu</span>
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuLabel>Actions</DropdownMenuLabel>
            <DropdownMenuItem
              onClick={() => navigator.clipboard.writeText(String(Project.id))}
            >
              copier l'ID du projet
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem>Voir tâches</DropdownMenuItem>
            <DropdownMenuItem>Modifier Projet</DropdownMenuItem>

          </DropdownMenuContent>
        </DropdownMenu>
      )
    },
  },
]