import pdfplumber, sys, json
with pdfplumber.open(sys.argv[1]) as pdf:
    out = {"paginas": []}
    for i, page in enumerate(pdf.pages):
        text = page.extract_text() or ""
        tables = page.extract_tables()
        tables_info = []
        for t in tables:
            tables_info.append({
                "filas": len(t),
                "columnas": len(t[0]) if t else 0,
                "primeras_2_filas": t[:2]
            })
        out["paginas"].append({
            "num": i + 1,
            "texto_completo": text,
            "tablas_detectadas": len(tables),
            "tablas": tables_info
        })
print(json.dumps(out, ensure_ascii=False, indent=2))