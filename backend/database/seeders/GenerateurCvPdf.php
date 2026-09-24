<?php

namespace Database\Seeders;

/** Génère un CV PDF texte d'une page, pour les données de démonstration. */
class GenerateurCvPdf
{
    /** @var array<int, array{0: string, 1: int, 2: bool}> texte, taille, gras */
    private array $lignes = [];

    public function titre(string $texte): self
    {
        $this->lignes[] = [$texte, 20, true];

        return $this;
    }

    public function section(string $texte): self
    {
        $this->lignes[] = ['', 6, false];
        $this->lignes[] = [mb_strtoupper($texte), 12, true];

        return $this;
    }

    public function ligne(string $texte, int $taille = 10): self
    {
        // Retour à la ligne simple pour ne pas déborder de la page.
        foreach (explode("\n", wordwrap($texte, 95, "\n", true)) as $morceau) {
            $this->lignes[] = [$morceau, $taille, false];
        }

        return $this;
    }

    public function contenu(): string
    {
        $y = 800;
        $flux = "BT\n";
        foreach ($this->lignes as [$texte, $taille, $gras]) {
            $y -= (int) round($taille * 1.45);
            $police = $gras ? 'F2' : 'F1';
            $flux .= sprintf("/%s %d Tf 1 0 0 1 50 %d Tm (%s) Tj\n", $police, $taille, $y, $this->echapper($texte));
        }
        $flux .= "ET\n";

        $objets = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            '<< /Length '.strlen($flux)." >>\nstream\n".$flux.'endstream',
        ];

        $pdf = "%PDF-1.4\n";
        $positions = [];
        foreach ($objets as $i => $objet) {
            $positions[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$objet."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objets) + 1)."\n0000000000 65535 f \n";
        foreach ($positions as $position) {
            $pdf .= sprintf("%010d 00000 n \n", $position);
        }
        $pdf .= "trailer\n<< /Size ".(count($objets) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";

        return $pdf;
    }

    /** UTF-8 vers WinAnsi, puis échappement des caractères réservés du PDF. */
    private function echapper(string $texte): string
    {
        $texte = str_replace(['’', '—', '–', '…'], ["'", '-', '-', '...'], $texte);
        $texte = mb_convert_encoding($texte, 'Windows-1252', 'UTF-8');

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $texte);
    }
}
